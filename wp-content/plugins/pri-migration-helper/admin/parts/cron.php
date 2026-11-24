<?php
$a_pmh_enabled_crons = (array) get_option( 'pmh_enabled_crons', array() );
 ?>
<div class="wrap">

	<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

		<table id="pmh-cron" class="form-table">

			<tbody>

				<tr>
					<th scope="row"><label for="pmh-cron[]"><?php esc_html_e( 'Media', 'pmh' ); ?></label></th>
					<td>
						<input
							type="checkbox"
							name="pmh-cron[]"
							value="media"
							<?php checked( in_array( 'media', $a_pmh_enabled_crons ) ? 1 : 0, 1 ); ?>
						>
						Process all media and make sure that their meta is correct.
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="pmh-cron[]"><?php esc_html_e( 'Posts', 'pmh' ); ?></label></th>
					<td>
						<input
							type="checkbox"
							name="pmh-cron[]"
							value="posts"
							<?php checked( in_array( 'posts', $a_pmh_enabled_crons ) ? 1 : 0, 1 ); ?>
						>
						Process all posts and fix image tag in their content. This process will only run if the above process found nothing to process.
					</td>
				</tr>

				<tr>
					<th scope="row"></th>
					<td>
						<input type="submit" value="Save" class="button button-primary">
						<input type="hidden" name="action" value="pmh-save-settings-cron">
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
