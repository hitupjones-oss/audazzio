<?php
/**
 * Elementor widgets generated from the component schemas.
 *
 * Each widget is a one-line subclass of AZ_Widget, which builds its controls from az_component_schemas()
 * and renders through az_component(): the same markup the shortcodes produce.
 * A new section needs its name in az_component_names() AND a class line at the bottom of this file.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

abstract class AZ_Widget extends Widget_Base {

	/** Component key, e.g. "hero". */
	abstract protected function az_name();

	protected function az_schema() {
		return az_component_schemas()[ $this->az_name() ];
	}

	public function get_name() {
		return 'az-' . $this->az_name();
	}

	public function get_title() {
		return $this->az_schema()['title'];
	}

	public function get_icon() {
		return $this->az_schema()['icon'] ?? 'eicon-apps';
	}

	public function get_categories() {
		return array( 'audazzio' );
	}

	public function get_keywords() {
		return array( 'audazzio', 'wave', $this->az_name() );
	}

	public function get_script_depends() {
		return array( 'az' );
	}

	public function get_style_depends() {
		return array( 'az' );
	}

	/** Some output depends on site settings (store links, demo clip), so never cache the markup. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Schema field -> Elementor control. */
	protected function az_control( $f ) {
		$c = array( 'label' => $f['label'] ?? '' );
		if ( ! empty( $f['description'] ) ) {
			$c['description'] = $f['description'];
		}
		switch ( $f['type'] ) {
			case 'textarea':
				$c += array( 'type' => Controls_Manager::TEXTAREA, 'rows' => $f['rows'] ?? 4, 'default' => $f['default'] ?? '', 'dynamic' => array( 'active' => true ) );
				break;
			case 'url':
				$c += array( 'type' => Controls_Manager::URL, 'default' => array( 'url' => is_array( $f['default'] ?? '' ) ? ( $f['default']['url'] ?? '' ) : ( $f['default'] ?? '' ) ), 'dynamic' => array( 'active' => true ), 'options' => false, 'label_block' => true );
				break;
			case 'switch':
				$c += array( 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => $f['default'] ?? '' );
				break;
			case 'select':
				$c += array( 'type' => Controls_Manager::SELECT, 'options' => $f['options'], 'default' => $f['default'] ?? '' );
				break;
			case 'number':
				$c += array( 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 999, 'default' => $f['default'] ?? 0 );
				break;
			case 'media':
				$def = $f['default'] ?? '';
				$c  += array( 'type' => Controls_Manager::MEDIA, 'media_types' => $f['media_types'] ?? array( 'image' ), 'default' => is_array( $def ) ? $def : array( 'url' => $def ), 'dynamic' => array( 'active' => true ) );
				break;
			default:
				$c += array( 'type' => Controls_Manager::TEXT, 'label_block' => true, 'default' => $f['default'] ?? '', 'dynamic' => array( 'active' => true ) );
		}
		return $c;
	}

	protected function register_controls() {
		$schema = $this->az_schema();
		$this->start_controls_section( 'az_content', array( 'label' => 'Content' ) );
		foreach ( $schema['fields'] as $key => $f ) {
			if ( 'repeater' !== $f['type'] ) {
				$this->add_control( $key, $this->az_control( $f ) );
			}
		}
		$this->add_control( 'az_hl_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html( 'In a headline, a new line starts a new line on the page, and *asterisks* set words in the lighter second tone.' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->end_controls_section();

		foreach ( $schema['fields'] as $key => $f ) {
			if ( 'repeater' !== $f['type'] ) {
				continue;
			}
			$this->start_controls_section( 'az_' . $key, array( 'label' => $f['label'] ) );
			$rep = new Repeater();
			foreach ( $f['fields'] as $sub_key => $sub ) {
				$rep->add_control( $sub_key, $this->az_control( $sub ) );
			}
			$this->add_control( $key, array(
				'label'       => $f['label'],
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'default'     => $f['default'] ?? array(),
				'title_field' => $f['title'] ?? '',
			) );
			$this->end_controls_section();
		}

		if ( ! empty( $schema['note'] ) ) {
			$this->start_controls_section( 'az_data', array( 'label' => 'Good to know' ) );
			$this->add_control( 'az_data_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html( $schema['note'] ), 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
			$this->end_controls_section();
		}
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$args     = array();
		foreach ( $this->az_schema()['fields'] as $key => $f ) {
			$args[ $key ] = $settings[ $key ] ?? ( $f['default'] ?? '' );
		}
		echo az_component( $this->az_name(), $args ); // phpcs:ignore -- components escape their own output.
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile
final class AZ_Widget_Hero extends AZ_Widget { protected function az_name() { return 'hero'; } }
final class AZ_Widget_Page_Hero extends AZ_Widget { protected function az_name() { return 'page-hero'; } }
final class AZ_Widget_Steps extends AZ_Widget { protected function az_name() { return 'steps'; } }
final class AZ_Widget_Flow extends AZ_Widget { protected function az_name() { return 'flow'; } }
final class AZ_Widget_Canvas extends AZ_Widget { protected function az_name() { return 'canvas'; } }
final class AZ_Widget_Try extends AZ_Widget { protected function az_name() { return 'try'; } }
final class AZ_Widget_Numbers extends AZ_Widget { protected function az_name() { return 'numbers'; } }
final class AZ_Widget_Videos extends AZ_Widget { protected function az_name() { return 'videos'; } }
final class AZ_Widget_Solutions extends AZ_Widget { protected function az_name() { return 'solutions'; } }
final class AZ_Widget_Solution extends AZ_Widget { protected function az_name() { return 'solution'; } }
final class AZ_Widget_Cases extends AZ_Widget { protected function az_name() { return 'cases'; } }
final class AZ_Widget_Logos extends AZ_Widget { protected function az_name() { return 'logos'; } }
final class AZ_Widget_Press extends AZ_Widget { protected function az_name() { return 'press'; } }
final class AZ_Widget_Quotes extends AZ_Widget { protected function az_name() { return 'quotes'; } }
final class AZ_Widget_Values extends AZ_Widget { protected function az_name() { return 'values'; } }
final class AZ_Widget_Story extends AZ_Widget { protected function az_name() { return 'story'; } }
final class AZ_Widget_People extends AZ_Widget { protected function az_name() { return 'people'; } }
final class AZ_Widget_Cta extends AZ_Widget { protected function az_name() { return 'cta'; } }
final class AZ_Widget_Join extends AZ_Widget { protected function az_name() { return 'join'; } }
final class AZ_Widget_Faq extends AZ_Widget { protected function az_name() { return 'faq'; } }
final class AZ_Widget_Prose extends AZ_Widget { protected function az_name() { return 'prose'; } }
