<?php

namespace Wpae\App\UnsecuredController;


use Wpae\App\Service\Addons\AddonNotFoundException;
use Wpae\App\Service\Addons\AddonService;
use Wpae\Controller\BaseController;
use Wpae\Http\Request;
use Wpae\Scheduling\Export;
use Wpae\Http\JsonResponse;

class SchedulingController extends BaseController
{
    /** Scheduling API Version */
    const VERSION = 1;

    /** @var Export */
    private $scheduledExportService;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->scheduledExportService = new Export();
    }

    public function triggerAction(Request $request)
    {
        if (!$this->isRequestValid()) {
            return new JsonResponse(array('message' => 'Export hash is invalid'), 401);
        }

        $refusal = $this->licenceGateRefusal();

        if ($refusal !== null) {
            return $refusal;
        }

        $exportId = intval($request->get('export_id'));

        $export = new \PMXE_Export_Record();
        $export->getById($exportId);

        if(isset($export->options['enable_real_time_exports']) && $export->options['enable_real_time_exports'] ) {
            wp_send_json(array(
                'status'     => 403,
                'message'    => sprintf(esc_html__('This export is configured to run as records are created and cannot be run via this method.', 'wp_all_export_plugin'), $id)
            ));
        }

        $this->disableExportsThatDontHaveAddon($export);

        if ($export->isEmpty()) {
            return new JsonResponse(array('message' => 'Export not found'), 404);
        }

        if ((int)$export->executing) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " is currently in manually process. Request skipped."), 409);
        }
        if ($export->processing and !$export->triggered) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " currently in process. Request skipped."), 409);

        }
        if (!$export->processing and $export->triggered) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " already triggered. Request skipped."), 409);
        }

        if (!$export->processing and !$export->triggered) {
            $this->scheduledExportService->trigger($export);

            return new JsonResponse(array('message' => "#" . $export->id . " Cron job triggered."));
        }

        return new JsonResponse(array("message" => "Can't process"), 500);
    }

    public function processAction(Request $request)
    {
        if (!$this->isRequestValid()) {
            return new JsonResponse(array('message' => 'Export hash is invalid'), 401);
        }

        $exportId = intval($request->get('export_id'));

        $export = new \PMXE_Export_Record();
        $export->getById($exportId);

        // Decided against the record, not the action: this is how a scheduled
        // run advances, one chunk per request, and refusing it outright would
        // abandon a run mid-flight. triggerAction() above, which is what starts
        // a run, is refused without consulting anything.
        $refusal = $this->licenceGateRefusal($export);

        if ($refusal !== null) {
            return $refusal;
        }

        if(isset($export->options['enable_real_time_exports']) && $export->options['enable_real_time_exports'] ) {
            wp_send_json(array(
                'status'     => 403,
                'message'    => sprintf(esc_html__('his export is configured to run as records are created and cannot be run via this method.', 'wp_all_export_plugin'), $id)
            ));
        }

        $this->disableExportsThatDontHaveAddon($export);

        if ($export->isEmpty()) {
            return new JsonResponse(array('message' => 'Export not found'), 404);
        }

        $logger = function($m) {
            echo "<p>" . esc_html($m). "</p>\\n";
        };

        if ($export->processing == 1 and (time() - strtotime($export->registered_on)) > 120) {
            // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
            $export->set(array(
                'processing' => 0
            ))->update();
        }

        // start execution imports that is in the cron process
        if (!(int)$export->triggered) {
            if (!empty($export->parent_id) or empty($queue_exports)) {
                return new JsonResponse(array("message" => 'Export #' . $exportId . ' is not triggered. Request skipped.'), 400);
            }
        } elseif ((int)$export->executing) {
            return new JsonResponse(array('message' => 'Export #' . $exportId . ' is currently in manually process. Request skipped.'), 409);
        } elseif ((int)$export->triggered and !(int)$export->processing) {

            try {
                $export->set(array('canceled' => 0))->execute($logger, true);
            } catch (AddonNotFoundException $e) {
                $this->denyScheduledRun($e->getMessage());
            }
            if (!(int)$export->triggered and !(int)$export->processing) {
                $this->scheduledExportService->process($export);
                return new JsonResponse(array('Export #' . $exportId . ' complete'), 201);
            } else {
                return new JsonResponse(array('message' => 'Records Processed ' . (int)$export->exported . '.'));
            }

        } else {
            return new JsonResponse(array('message' => 'Export #' . $exportId . ' already processing. Request skipped.'), 409);
        }

        return new JsonResponse(array("message" => "Can't process"), 500);
    }

    // 403, not the 401 used above: a licence refusal is authenticated but unauthorised.
    private function licenceGateRefusal($export = null)
    {
        $gate = \Wpae\App\Gate\Gate::instance();

        $refused = $export === null ? $gate->blocks() : $gate->blocksRun($export);

        if (! $refused) {
            return null;
        }

        if ($export !== null) {
            $gate->stopRun($export);
        }

        return new JsonResponse(array(
            'status' => 403,
            'code' => 'license_required',
            'message' => $export === null
                ? $gate->apiRefusal()
                : $gate->stopRefusal($export),
        ), 403);
    }

    public function versionAction()
    {
        return new JsonResponse(array('version' => self::VERSION));
    }

    /**
     * @return bool
     */
    private function isRequestValid()
    {
        $cron_job_key = \PMXE_Plugin::getInstance()->getOption('cron_job_key');
        return
            !empty($cron_job_key) and
            !empty($_GET['export_id']) and
            !empty($_GET['export_key']) and
            hash_equals( $cron_job_key, sanitize_text_field( wp_unslash( $_GET['export_key'] ) ) );
    }

    /**
     * @param $export
     */
    private function disableExportsThatDontHaveAddon($export)
    {
        // Authenticated scheduled request: let the addon-installer SDK self-heal a missing
        // companion before the add-on checks below decide whether to stop the export.
        do_action('pmxe_scheduled_request_authenticated', $export->id);

        $cpt = $export->options['cpt'];
        if (!is_array($cpt)) {
            $cpt = array($cpt);
        }

        $addons = new AddonService();

        if (
            (in_array('users', $cpt) && !$addons->isUserAddonActive())
            ||
            ((in_array('shop_customer', $cpt) || in_array('shop_guest_customer', $cpt)) && !$addons->isUserProAddonActive())
            ||
            ($export->options['export_type'] == 'advanced' && $export->options['wp_query_selector'] == 'wp_user_query' && !$addons->isUserAddonActive())
        ) {
            $this->denyScheduledRun(\__('The User Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', 'wp_all_export_plugin'));
        }

        if (strpos(reset($cpt), 'custom_') === 0 && !class_exists('GF_Export_Add_On')) {
            $this->denyScheduledRun(\__('The Gravity Forms Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', 'wp_all_export_plugin'));
        }

        if (
            (( (in_array('product', $cpt) && in_array('product_variation', $cpt) && !$addons->isWooCommerceProductAddonActive()) || (in_array('shop_order', $cpt) && !$addons->isWooCommerceOrderAddonActive()) || in_array('shop_coupon', $cpt) || in_array('shop_review', $cpt) ) && !$addons->isWooCommerceAddonActive())
            ||
            ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array('shop_coupon')) && !$addons->isWooCommerceAddonActive())
	        ||
            ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array('shop_order')) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceOrderAddonActive())
	        ||
            ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array(array('product', 'product_variation'), )) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceProductAddonActive())
        ) {
            $this->denyScheduledRun(\__('The WooCommerce Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', 'wp_all_export_plugin'));
        }

        if((in_array('acf', $export->options['cc_type']) || $export->options['xml_template_type'] == 'custom' && in_array('acf', $export->options['custom_xml_template_options']['cc_type'])) && !$addons->isAcfAddonActive()) {
            $this->denyScheduledRun(\__('The ACF Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', 'wp_all_export_plugin'));
        }

	    // Block Google Merchant Exports if the supporting add-on isn't active.
	    if(isset($export->options['xml_template_type']) && $export->options['xml_template_type'] == \XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS && !$addons->isWooCommerceAddonActive()) {

		    $this->denyScheduledRun(\__('The WooCommerce Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', 'wp_all_export_plugin'));

	    }
    }

    /**
     * Ends a scheduled run that cannot proceed, with a status the caller can act on.
     *
     * These refusals used to be a bare die(), which answers 200. Cron runners and monitors read the
     * status code, so a refusal at 200 reads as a completed run and the skipped export repeats
     * unnoticed. 424 matches what the addon-installer SDK returns for the same class of refusal.
     *
     * @param string $message
     * @return void
     */
    private function denyScheduledRun($message)
    {
        $response = new JsonResponse(array('message' => $message), 424);
        $response->render();
    }

}