<?php
/**
 * Media Metadata List
 *
 * @package    Media Metadata List
 * @subpackage MediaMetadataList Main Functions
/*
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$mediametadatalist = new MediaMetadataList();

/** ==================================================
 * Main Functions
 */
class MediaMetadataList {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  upload_url.
	 */
	private $upload_url;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		list( $this->upload_dir, $this->upload_url, $upload_path ) = $this->upload_dir_url_path();

		add_filter( 'manage_media_columns', array( $this, 'muc_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'muc_value' ), 10, 2 );
	}

	/** ==================================================
	 * Media Library Column
	 *
	 * @param array $cols  cols.
	 * @return array $cols
	 * @since 1.00
	 */
	public function muc_column( $cols ) {

		global $pagenow;
		if ( 'upload.php' == $pagenow ) {
			$cols['media_metadata_list'] = __( 'Metadata' );
		}

		return $cols;
	}

	/** ==================================================
	 * Media Library Column
	 *
	 * @param string $column_name  column_name.
	 * @param int    $id  id.
	 * @since 1.00
	 */
	public function muc_value( $column_name, $id ) {

		if ( 'media_metadata_list' == $column_name ) {
			$filetype = wp_check_filetype( get_attached_file( $id ) );
			$type     = wp_ext2type( $filetype['ext'] );
			$metadata = wp_get_attachment_metadata( $id );
			$path_file  = get_post_meta( $id, '_wp_attached_file', true );
			$filename   = wp_basename( $path_file );
			$media_path = str_replace( $filename, '', $path_file );
			$media_url  = $this->upload_url . '/' . $media_path;
			?>
			<div><?php esc_html_e( 'folder' ); ?>: <?php echo esc_html( $media_path ); ?></div>
			<?php
			if ( 'image' === $type || ( ! empty( $metadata ) && array_key_exists( 'sizes', $metadata ) ) ) {

				$size_wh = $metadata['width'] . 'x' . $metadata['height'];
				$file_size = size_format( $metadata['filesize'], 2 );
				?>
				<div><?php esc_html_e( 'Size' ); ?>: <?php echo esc_html( $size_wh . '&nbsp;' . $file_size ); ?></div>
				<?php
				if ( ! empty( $metadata['original_image'] ) ) {
					$file_path = wp_get_original_image_path( $id );
					$bytes_org = filesize( $file_path );
					$org_size  = size_format( $bytes_org, 2 );
					$org_url   = wp_get_original_image_url( $id );
					?>
					<details>
					<summary><?php esc_html_e( 'Original File', 'media-metadata-list' ); ?></summary>
					<div>
					<a style="text-decoration: none;" href="<?php echo esc_url( $org_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $metadata['original_image'] . '&nbsp;' . $org_size ); ?></a>
					</div>
					</details>
					<?php
				}

				$thumbnails = $metadata['sizes'];
				if ( ! empty( $thumbnails ) ) {
					?>
					<details>
					<summary><?php esc_html_e( 'Thumbnail' ); ?></summary>
					<?php
					foreach ( $thumbnails as $key => $key2 ) {
						if ( array_key_exists( 'sources', $thumbnails[ $key ] ) ) {
							/* WP6.1 or later */
							$sources = $thumbnails[ $key ]['sources'];
							foreach ( $sources as $key2 => $value2 ) {
								$filename = $sources[ $key2 ]['file'];
								$filesize = size_format( $sources[ $key2 ]['filesize'], 2 );
								$url      = $media_url . $sources[ $key2 ]['file'];
								?>
								<div>
								<a style="text-decoration: none;" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $filename . '&nbsp;' . $filesize ); ?></a>
								</div>
								<?php
							}
						} else {
							$filename = $key2['file'];
							$filesize = size_format( $key2['filesize'], 2 );
							$url      = $media_url . $key2['file'];
							?>
							<div>
							<a style="text-decoration: none;" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $filename . '&nbsp;' . $filesize ); ?></a>
							</div>
							<?php
						}
					}
					?>
					</details>
					<?php
				}
				if ( array_key_exists( 'sources', $metadata ) ) {
					?>
					<details>
					<summary><?php esc_html_e( 'Sources', 'media-metadata-list' ); ?></summary>
					<?php
					foreach ( $metadata['sources'] as $key => $value ) {
						$url_sources = $media_url . $value['file'];
						?>
						<div>
						<a style="text-decoration: none;" href="<?php echo esc_url( $url_sources ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $value['file'] ) . '&nbsp;' . esc_html( size_format( $value['filesize'], 2 ) ); ?></a>
						</div>
						<?php
					}
					?>
					</details>
					<?php
				}
				$exifdatas = $this->exifdata( $id, $metadata );
				if ( ! empty( $exifdatas ) ) {
					?>
					<details>
					<summary>Exif</summary>
					<?php
					foreach ( $exifdatas as $key => $value ) {
						?>
						<div>
						<?php echo esc_html( $key ); ?>
						<?php echo esc_html( $value ); ?>
						</div>
						<?php
					}
					?>
					</details>
					<?php
				}
			} else if ( 'audio' === $type ) {
				$metadata_audio = wp_read_audio_metadata( get_attached_file( $id ) );
				$file_size = size_format( $metadata_audio['filesize'], 2 );
				$mimetype  = $metadata_audio['fileformat'] . '(' . $metadata_audio['mime_type'] . ')';
				$length    = $metadata_audio['length_formatted'];
				?>
				<div><?php esc_html_e( 'File size:' ); ?><?php echo esc_html( $file_size ); ?></div>
				<div><?php esc_html_e( 'File type:' ); ?><?php echo esc_html( $mimetype ); ?></div>
				<div><?php esc_html_e( 'Length:' ); ?><?php echo esc_html( $length ); ?></div>
				<?php
			} else if ( 'video' === $type ) {
				$metadata_video = wp_read_video_metadata( get_attached_file( $id ) );
				$file_size = size_format( $metadata_video['filesize'], 2 );
				$mimetype  = $metadata_video['fileformat'] . '(' . $metadata_video['mime_type'] . ')';
				$length    = $metadata_video['length_formatted'];
				?>
				<div><?php esc_html_e( 'File size:' ); ?><?php echo esc_html( $file_size ); ?></div>
				<div><?php esc_html_e( 'File type:' ); ?><?php echo esc_html( $mimetype ); ?></div>
				<div><?php esc_html_e( 'Length:' ); ?><?php echo esc_html( $length ); ?></div>
				<?php
			} else {
				$bytes     = filesize( get_attached_file( $id ) );
				$file_size = size_format( $bytes, 2 );
				$filetype  = wp_check_filetype( get_attached_file( $id ) );
				$mimetype  = $filetype['ext'] . '(' . $filetype['type'] . ')';
				?>
				<div><?php esc_html_e( 'File size:' ); ?><?php echo esc_html( $file_size ); ?></div>
				<?php
				if ( ! empty( $filetype['type'] ) ) {
					?>
					<div><?php esc_html_e( 'File type:' ); ?><?php echo esc_html( $mimetype ); ?></div>
					<?php
				}
			}
		}
	}

	/** ==================================================
	 * Upload Path
	 *
	 * @return array $upload_dir,$upload_url,$upload_path  uploadpath.
	 * @since 1.00
	 */
	private function upload_dir_url_path() {

		$wp_uploads = wp_upload_dir();

		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$relationalpath = substr( $wp_uploads['baseurl'], $relation_path_true );
			$basepath       = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url     = $this->realurl( $basepath, $relationalpath );
			$upload_dir     = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url = $wp_uploads['baseurl'];
			$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}

		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}

		if ( $relation_path_true > 0 ) {
			$upload_path = $relationalpath;
		} else {
			$upload_path = str_replace( site_url( '/' ), '', $upload_url );
		}

		$upload_dir  = untrailingslashit( $upload_dir );
		$upload_url  = untrailingslashit( $upload_url );
		$upload_path = untrailingslashit( $upload_path );

		return array( $upload_dir, $upload_url, $upload_path );
	}


	/** ==================================================
	 * Exif caption
	 *
	 * @param int   $attach_id  attach_id.
	 * @param array $metadata  metadata.
	 * @return array $exifdatas  $exifdatas.
	 * @since 1.00
	 */
	private function exifdata( $attach_id, $metadata ) {

		$exifdatas = array();
		if ( $metadata['image_meta']['title'] ) {
			$exifdatas['title'] = $metadata['image_meta']['title'];
		}
		if ( $metadata['image_meta']['credit'] ) {
			$exifdatas['credit'] = $metadata['image_meta']['credit'];
		}
		if ( $metadata['image_meta']['camera'] ) {
			$exifdatas['camera'] = $metadata['image_meta']['camera'];
		}
		if ( $metadata['image_meta']['caption'] ) {
			$exifdatas['caption'] = $metadata['image_meta']['caption'];
		}
		$exif_ux_time = $metadata['image_meta']['created_timestamp'];
		if ( ! empty( $exif_ux_time ) ) {
			if ( function_exists( 'wp_date' ) ) {
				$exifdatas['created_timestamp'] = wp_date( 'Y-m-d H:i:s', $exif_ux_time, new DateTimeZone( 'UTC' ) );
			} else {
				$exifdatas['created_timestamp'] = date_i18n( 'Y-m-d H:i:s', $exif_ux_time, false );
			}
		} else {
			$file_path = get_attached_file( $attach_id );
			$mimetype = get_post_mime_type( $attach_id );
			if ( 'image/jpeg' === $mimetype || 'image/tiff' === $mimetype ) {
				$shooting_date_time = null;
				$exif = @exif_read_data( $file_path, 'FILE', true );
				if ( isset( $exif['EXIF']['DateTimeOriginal'] ) && ! empty( $exif['EXIF']['DateTimeOriginal'] ) ) {
					$shooting_date_time = $exif['EXIF']['DateTimeOriginal'];
				} else if ( isset( $exif['IFD0']['DateTime'] ) && ! empty( $exif['IFD0']['DateTime'] ) ) {
					$shooting_date_time = $exif['IFD0']['DateTime'];
				}
				if ( ! empty( $shooting_date_time ) ) {
					$shooting_date = str_replace( ':', '-', substr( $shooting_date_time, 0, 10 ) );
					$shooting_time = substr( $shooting_date_time, 10 );
					$exifdatas['created_timestamp'] = $shooting_date . $shooting_time;
				}
			}
		}
		if ( $metadata['image_meta']['copyright'] ) {
			$exifdatas['copyright'] = $metadata['image_meta']['copyright'];
		}
		if ( $metadata['image_meta']['aperture'] ) {
			$exifdatas['aperture'] = 'f/' . $metadata['image_meta']['aperture'];
		}
		if ( $metadata['image_meta']['shutter_speed'] ) {
			if ( $metadata['image_meta']['shutter_speed'] < 1 ) {
				$shutter = round( 1 / $metadata['image_meta']['shutter_speed'] );
				$exifdatas['shutter_speed'] = '1/' . $shutter . 'sec';
			} else {
				$exifdatas['shutter_speed'] = $metadata['image_meta']['shutter_speed'] . 'sec';
			}
		}
		if ( $metadata['image_meta']['iso'] ) {
			$exifdatas['iso'] = 'ISO-' . $metadata['image_meta']['iso'];
		}
		if ( $metadata['image_meta']['focal_length'] ) {
			$exifdatas['focal_length'] = $metadata['image_meta']['focal_length'] . 'mm';
		}
		if ( $metadata['image_meta']['orientation'] ) {
			$ort_no = $metadata['image_meta']['orientation'];
			$ort_text = null;
			switch ( $ort_no ) {
				case 1:
					$ort_text = __( 'Horizontal (normal)', 'media-metadata-list' );
					break;
				case 2:
					$ort_text = __( 'Mirror horizontal', 'media-metadata-list' );
					break;
				case 3:
					$ort_text = __( 'Rotate 180', 'media-metadata-list' );
					break;
				case 4:
					$ort_text = __( 'Mirror vertical', 'media-metadata-list' );
					break;
				case 5:
					$ort_text = __( 'Mirror horizontal and rotate 270 CW', 'media-metadata-list' );
					break;
				case 6:
					$ort_text = __( 'Rotate 90 CW', 'media-metadata-list' );
					break;
				case 7:
					$ort_text = __( 'Mirror horizontal and rotate 90 CW', 'media-metadata-list' );
					break;
				case 8:
					$ort_text = __( 'Rotate 270 CW', 'media-metadata-list' );
					break;
			}
			$exifdatas['orientation'] = $ort_text;
		}

		return $exifdatas;
	}
}


