<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class GDB_Gold_Wallet_Widget extends GDB_Base_Widget {

    public function get_name() {
        return 'gdb-gold-wallet';
    }

    public function get_title() {
        return __('کیف پول طلا - خرید', 'golden-dashboard');
    }

    public function get_icon() {
        return 'eicon-coins';
    }

    public function get_keywords() {
        return ['gold', 'wallet', 'silver', 'metal'];
    }

    public function get_script_depends() {
        return ['gdb-script', 'gdb-common', 'gdb-gold-wallet'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('محتوا', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $types = class_exists('GDB_Gold_Wallet') ? GDB_Gold_Wallet::get_types(true) : [];
        $type_options = [0 => __('-- انتخاب کنید --', 'golden-dashboard')];
        foreach ($types as $type) {
            $type_options[$type->id] = $type->name;
        }

        $this->add_control(
            'gold_type_id',
            [
                'label'       => __('نوع کیف پول طلا', 'golden-dashboard'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $type_options,
                'default'     => 0,
                'description' => __('انواع از تب «کیف پول طلا» در تنظیمات پنل مدیریت تعریف می‌شوند.', 'golden-dashboard'),
            ]
        );

        $this->add_control('show_title', ['label' => __('نمایش عنوان', 'golden-dashboard'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('title', ['label' => __('عنوان', 'golden-dashboard'), 'type' => Controls_Manager::TEXT, 'default' => __('خرید طلا', 'golden-dashboard'), 'condition' => ['show_title' => 'yes']]);

        $this->add_control('placeholder_text', ['label' => __('متن راهنمای فیلد', 'golden-dashboard'), 'type' => Controls_Manager::TEXT, 'default' => __('مقدار را وارد کنید', 'golden-dashboard')]);
        $this->add_control('button_text', ['label' => __('متن دکمه خرید', 'golden-dashboard'), 'type' => Controls_Manager::TEXT, 'default' => __('خرید', 'golden-dashboard')]);

        $this->end_controls_section();

        $this->register_common_style_controls();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $type_id = absint($settings['gold_type_id'] ?? 0);

        if (!class_exists('GDB_Gold_Wallet') || !$type_id) {
            echo '<div class="gdb-card gdb-gold-wallet-widget"><p>' . esc_html__('لطفاً از تنظیمات ویجت، یک نوع کیف پول طلا انتخاب کنید.', 'golden-dashboard') . '</p></div>';
            return;
        }

        $type = GDB_Gold_Wallet::get_type($type_id);
        if (!$type || $type->status !== 'active') {
            echo '<div class="gdb-card gdb-gold-wallet-widget"><p>' . esc_html__('این نوع کیف پول طلا در حال حاضر در دسترس نیست.', 'golden-dashboard') . '</p></div>';
            return;
        }

        $price = GDB_Gold_Wallet::get_live_price($type);
        $suggested = array_filter(array_map('trim', explode(',', (string) $type->suggested_amounts)));
        $user_id = get_current_user_id();
        $gold_balance = $user_id ? GDB_Gold_Wallet::balance($user_id, $type->id) : 0;
        ?>
        <div class="gdb-card gdb-gold-wallet-widget" data-type-id="<?php echo esc_attr($type->id); ?>" data-price-per-unit="<?php echo esc_attr($price); ?>" data-wallet-balance="<?php echo esc_attr($user_id ? gdb_display_amount(GDB_Wallet::balance($user_id)) : 0); ?>">
            <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])) : ?>
                <div class="gdb-title"><?php echo esc_html($settings['title']); ?></div>
            <?php endif; ?>

            <div class="gdb-gold-wallet-price-row">
                <span><?php _e('قیمت لحظه‌ای هر', 'golden-dashboard'); ?> <?php echo esc_html($type->unit_label); ?> <?php echo esc_html($type->name); ?>:</span>
                <strong class="gdb-gold-wallet-unit-price"><?php echo wp_kses_post(wc_price($price)); ?></strong>
            </div>

            <?php if ($user_id) : ?>
            <div class="gdb-gold-wallet-balance-row">
                <span><?php _e('موجودی فعلی شما:', 'golden-dashboard'); ?></span>
                <strong class="gdb-gold-wallet-my-balance"><?php echo esc_html(rtrim(rtrim(number_format($gold_balance, 3), '0'), '.')); ?> <?php echo esc_html($type->unit_label); ?></strong>
            </div>
            <?php endif; ?>

            <form class="gdb-gold-wallet-form" data-ajax="1">
                <?php wp_nonce_field('gdb_gold_wallet_nonce', 'gdb_gold_wallet_nonce'); ?>
                <input type="hidden" name="type_id" value="<?php echo esc_attr($type->id); ?>">
                <?php
                    $gdb_current_url = gdb_get_current_url_clean();
                ?>
                <input type="hidden" name="gdb_return_url" value="<?php echo esc_url($gdb_current_url); ?>">

                <?php if (!empty($suggested)) : ?>
                    <div class="gdb-suggested-grid">
                        <?php foreach ($suggested as $amt) :
                            $amt = floatval($amt);
                            if ($amt > 0) : ?>
                                <button type="button" class="gdb-suggested-btn" data-amount="<?php echo esc_attr($amt); ?>"><?php echo esc_html($amt); ?> <?php echo esc_html($type->unit_label); ?></button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="gdb-input-group">
                    <label class="gdb-input-label"><?php echo esc_html($settings['placeholder_text']); ?> (<?php echo esc_html($type->unit_label); ?>)</label>
                    <input type="number"
                           name="quantity"
                           class="gdb-amount-input gdb-gold-wallet-quantity"
                           min="<?php echo esc_attr($type->min_amount ?: $type->step_amount); ?>"
                           <?php if ($type->max_amount > 0) : ?>max="<?php echo esc_attr($type->max_amount); ?>"<?php endif; ?>
                           step="<?php echo esc_attr($type->step_amount ?: 0.1); ?>"
                           placeholder="<?php echo esc_attr($settings['placeholder_text']); ?>"
                           required>
                    <div class="gdb-quantity-warning" style="display:none; color:#dc2626; font-size:12px; margin-top:6px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:6px 10px;"></div>
                </div>

                <div class="gdb-gold-wallet-total-row">
                    <span><?php _e('مبلغ قابل پرداخت:', 'golden-dashboard'); ?></span>
                    <strong class="gdb-gold-wallet-total-price">-</strong>
                </div>

                <?php if ($user_id && $type->payment_mode === 'wallet_or_gateway') :
                    $wallet_balance_plain = gdb_wc_price_plain(gdb_display_amount(GDB_Wallet::balance($user_id)));
                ?>
                    <label class="gdb-gold-wallet-use-balance-row">
                        <input type="checkbox" name="use_wallet_balance" value="1" class="gdb-gold-wallet-use-balance">
                        <span>
                            <?php _e('پرداخت بخشی از این سفارش با موجودی کیف پولم و باقی‌مانده از طریق درگاه', 'golden-dashboard'); ?>
                            <br>
                            <small>(<?php printf(esc_html__('موجودی فعلی: %s', 'golden-dashboard'), esc_html($wallet_balance_plain)); ?>)</small>
                        </span>
                    </label>
                    <div class="gdb-gold-wallet-split-info" style="display:none;"></div>
                <?php endif; ?>

                <div class="gdb-gold-wallet-message gdb-topup-message" style="display:none; margin-top:12px;"></div>

                <div class="gdb-topup-submit-wrap">
                    <button type="submit" class="gdb-topup-submit gdb-gold-wallet-submit"><?php echo esc_html($settings['button_text']); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
}
