<?php

if (!class_exists('TaxoPress_Pro_Auto_Terms_Schedule')) {
    /**
     * class TaxoPress_Pro_Auto_Terms_Schedule
     */
    class TaxoPress_Pro_Auto_Terms_Schedule
    {
        public static $instance;

        public function __construct()
        {

            add_action('taxopress_schedule_frequency_fields', [$this, 'taxopress_pro_schedule_frequency_fields']);
            add_action('taxopress_schedule_cron_events', [$this, 'schedule_pro_cron_events']);
            add_action('taxopress_clear_schedule_cron_hooks', [$this, 'clear_pro_cron_hooks']);
            
            add_action('taxopress_cron_autoterms_hourly', [$this, 'taxopress_cron_autoterms_hourly_execution']);
            add_action('taxopress_cron_autoterms_daily', [$this, 'taxopress_cron_autoterms_daily_execution']);
        }

        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function taxopress_pro_schedule_frequency_fields($cron_schedule)
        {
            ?>
            <label>
                <input
                    type="radio"
                    class="autoterm_cron_radio"
                    id="autoterm_cron_hourly"
                    name="taxopress_autoterm_schedule[cron_schedule_choice]"
                    value="hourly"
                    <?php echo ($cron_schedule === 'hourly') ? 'checked' : ''; ?>
                />
                <?php esc_html_e('Hourly', 'taxopress-pro'); ?>
            </label>
            <br /><br />
            
            <label>
                <input
                    type="radio"
                    class="autoterm_cron_radio"
                    id="autoterm_cron_daily"
                    name="taxopress_autoterm_schedule[cron_schedule_choice]"
                    value="daily"
                    <?php echo ($cron_schedule === 'daily') ? 'checked' : ''; ?>
                />
                <?php esc_html_e('Daily', 'taxopress-pro'); ?>
            </label>
            <br /><br />
            <?php
        }

        public function schedule_pro_cron_events($cron_schedule)
        {
            if ($cron_schedule == 'hourly') {
                wp_schedule_event(time(), 'hourly', 'taxopress_cron_autoterms_hourly');
            } elseif ($cron_schedule == 'daily') {
                wp_schedule_event(time(), 'daily', 'taxopress_cron_autoterms_daily');
            }
        }

        public function clear_pro_cron_hooks()
        {
            wp_clear_scheduled_hook('taxopress_cron_autoterms_hourly');
            wp_clear_scheduled_hook('taxopress_cron_autoterms_daily');
        }

        public function taxopress_cron_autoterms_hourly_execution()
        {
            if (class_exists('SimpleTags_Client_Schedule')) {
                $schedule_instance = SimpleTags_Client_Schedule::get_instance();
                $schedule_instance->taxopress_cron_autoterms_execution('hourly');
            }
        }

        public function taxopress_cron_autoterms_daily_execution()
        {
            if (class_exists('SimpleTags_Client_Schedule')) {
                $schedule_instance = SimpleTags_Client_Schedule::get_instance();
                $schedule_instance->taxopress_cron_autoterms_execution('daily');
            }
        }
    }
}
