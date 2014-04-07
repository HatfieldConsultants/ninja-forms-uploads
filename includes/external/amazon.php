<?php

require_once( NINJA_FORMS_UPLOADS_DIR. '/includes/lib/s3/s3.php' );

class External_Amazon extends Ninja_Forms_Upload\External {

	private $title = 'Amazon S3';

	private $slug = 'amazon';

	private $settings;

	private $connected_settings;

	private $file_path = false;

	function __construct() {
		$this->setSettings();
		parent::__construct( $this->title , $this->slug, $this->settings );
	}

	private function setSettings() {
		$this->settings = array(
			array(
				'name' => 'amazon_s3_access_key',
				'type' => 'text',
				'label' => __( 'Access Key', 'ninja-forms-uploads' ),
				'desc' => '',
			),
			array(
				'name' => 'amazon_s3_secret_key',
				'type' => 'text',
				'label' => __( 'Secret Key', 'ninja-forms-uploadss' ),
				'desc' => '',
			),
			array(
				'name' => 'amazon_s3_bucket_name',
				'type' => 'text',
				'label' => __( 'Bucket Name', 'ninja-forms-uploads' ),
				'desc' => '',
			),
			array(
				'name' => 'amazon_s3_file_path',
				'type' => 'text',
				'label' => __( 'File Path', 'ninja-forms-uploads' ),
				'desc' => 'The default file path in the bucket where the file will be uploaded to',
				'default_value'	=> 'ninja-forms/'
			),
		);
	}

	protected function is_connected( $data = null ){
		if (!$data) {
			$data = get_option( 'ninja_forms_settings' );
		}
		if ( (isset($data['amazon_s3_access_key']) && $data['amazon_s3_access_key'] != '') &&
				 (isset($data['amazon_s3_secret_key']) && $data['amazon_s3_secret_key'] != '') &&
			 		(isset($data['amazon_s3_bucket_name']) && $data['amazon_s3_bucket_name'] != '') &&
			 			(isset($data['amazon_s3_file_path']) && $data['amazon_s3_file_path'] != '')
		) {
			$settings = array();
			$settings['access_key'] = $data['amazon_s3_access_key'];
			$settings['secret_key'] = $data['amazon_s3_secret_key'];
			$settings['bucket_name'] = $data['amazon_s3_bucket_name'];
			$settings['file_path'] = $data['amazon_s3_file_path'];
			$this->connected_settings = $settings;
			return true;
		}

		return false;
	}

	private function prepare() {
		if ( ! $this->file_path ) {
			$this->file_path = $this->sanitize_path( $this->connected_settings['file_path'] );
		}
		return new S3( $this->connected_settings['access_key'], $this->connected_settings['secret_key'] );
	}

	protected function upload_file( $filename ) {
		$s3 = $this->prepare();
		$s3->putObjectFile($filename, $this->connected_settings['bucket_name'], $this->file_path . basename( $filename ), S3::ACL_PUBLIC_READ);
	}

	private function sanitize_path( $path ) {
		$path = ltrim( $path, '/' );
		$path = rtrim( $path, '/' );
		return $path .'/';
	}

	public function file_url( $filename ) {
		$s3 = $this->prepare();
		return $s3->getAuthenticatedURL( $this->connected_settings['bucket_name'], $this->file_path . $filename, 3600 );
	}
}