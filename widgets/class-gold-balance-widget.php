<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class GDB_Gold_Balance_Widget extends GDB_Base_Widget {

    public function get_name() {
        return 'gdb-gold-balance';
    }

    public function get_title() {
        return __('کیف پول طلا - موجودی', 'golden-dashboard');
    }

    public function get_icon() {
        return 'eicon-coins';
    }

    public function get_keywords() {
        return ['gold', 'wallet', 'balance', 'silver', 'metal'];
    }

    public function get_script_depends() {
        return ['gdb-script', 'gdb-common', 'gdb-gold-wallet'];
    }

    protected function register_controls() {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'golden-dashboard'), 'tab' => Controls_Manager::TAB_CONTENT]);
        $this->add_control('show_title', ['label' => __('نمایش عنوان', 'golden-dashboard'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('title', ['label' => __('عنوان', 'golden-dashboard'), 'type' => Controls_Manager::TEXT, 'default' => __('موجودی کیف پول طلا', 'golden-dashboard'), 'condition' => ['show_title' => 'yes']]);
        $this->add_control('empty_text', ['label' => __('متن حالت خالی', 'golden-dashboard'), 'type' => Controls_Manager::TEXT, 'default' => __('شما هنوز طلایی خریداری نکرده‌اید.', 'golden-dashboard')]);
        $this->end_controls_section();

        $this->register_common_style_controls();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();

        if (!$user_id || !class_exists('GDB_Gold_Wallet')) {
            return;
        }

        $balances = GDB_Gold_Wallet::get_user_balances($user_id);
        ?>
        <div class="gdb-card gdb-gold-balance-widget" data-gdb-gold-balance-widget="1" data-empty-text="<?php echo esc_attr($settings['empty_text']); ?>">
            <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])) : ?>
                <div class="gdb-title"><?php echo esc_html($settings['title']); ?></div>
            <?php endif; ?>

            <div class="gdb-gold-balance-content">
                <?php echo wp_kses_post(GDB_Gold_Wallet::render_balances_html($user_id, $settings['empty_text'])); ?>
            </div>
        </div>
        <?php
    }
}
