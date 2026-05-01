<?php
/**
 * Reusable hero section component
 * 
 * @param string $gradient_id Unique ID for the SVG gradient
 * @param string $stop_color_1 First gradient color
 * @param string $stop_color_2 Second gradient color
 * @param string $icon FontAwesome icon class
 * @param string $title Hero title
 * @param string $description Hero description (optional)
 */
function renderHeroSection(string $gradient_id, string $stop_color_1, string $stop_color_2, string $icon, string $title, string $description = ''): void {
?>
<section class="hero section-hero <?php echo strtolower(str_replace(['#', 'HeroGradient'], '', $gradient_id)); ?>-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="<?php echo $gradient_id; ?>" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="<?php echo $stop_color_1; ?>"/>
                    <stop offset="100%" stop-color="<?php echo $stop_color_2; ?>"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#<?php echo $gradient_id; ?>)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="<?php echo htmlspecialchars($icon); ?>"></i> <?php echo htmlspecialchars($title); ?>
        </h2>
        <?php if ($description): ?>
        <p class="hero-desc">
            <?php echo htmlspecialchars($description); ?>
        </p>
        <?php endif; ?>
    </div>
</section>
<?php
}
