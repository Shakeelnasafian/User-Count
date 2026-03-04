<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Insufficient permissions.', 'user-count' ) );
}

$from_date = '';
$to_date   = '';

$has_submit = isset( $_GET['from_date'] ) || isset( $_GET['to_date'] );

if ( $has_submit ) {
	$nonce = isset( $_GET['user_count_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['user_count_nonce'] ) ) : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'user_count_search' ) ) {
		wp_die( __( 'Invalid nonce.', 'user-count' ) );
	}

	$from_raw = isset( $_GET['from_date'] ) ? sanitize_text_field( wp_unslash( $_GET['from_date'] ) ) : '';
	$to_raw   = isset( $_GET['to_date'] ) ? sanitize_text_field( wp_unslash( $_GET['to_date'] ) ) : '';

	$validate_date = static function ( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$date = DateTime::createFromFormat( 'Y-m-d', $value );
		if ( $date && $date->format( 'Y-m-d' ) === $value ) {
			return $value;
		}

		return '';
	};

	$from_date = $validate_date( $from_raw );
	$to_date   = $validate_date( $to_raw );
}

$table = new User_Count_List_Table();
$table->prepare_items( $from_date, $to_date );
?>

<div class="wrap user_count">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'User Count', 'user-count' ); ?></h1>

	<form method="get" class="user-count-filter">
		<?php wp_nonce_field( 'user_count_search', 'user_count_nonce' ); ?>
		<input type="hidden" name="page" value="editor_counter" />

		<label for="from_date"><?php esc_html_e( 'From', 'user-count' ); ?></label>
		<input type="date" id="from_date" name="from_date" value="<?php echo esc_attr( $from_date ); ?>" />

		<label for="to_date"><?php esc_html_e( 'To', 'user-count' ); ?></label>
		<input type="date" id="to_date" name="to_date" value="<?php echo esc_attr( $to_date ); ?>" />

		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'user-count' ); ?>" />
	</form>

	<?php $table->display(); ?>
</div>
