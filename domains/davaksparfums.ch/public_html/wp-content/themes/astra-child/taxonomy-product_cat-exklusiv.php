<?php
/**
 * Custom Template for Exclusive Category (exklusiv)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); 
?>

<div class="davaks-exclusive-page" style="background-color: #0b0b0b; color: #ccc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; min-height: 100vh;">

    <!-- 1️⃣ HERO — DECLARATION -->
    <!-- Vertical, dark, spacious, dominant image -->
    <section class="exclusive-hero" style="position: relative; height: 90vh; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 0;">
        <!-- Background Image -->
        <div class="hero-bg" style="position: absolute; top:0; left:0; width:100%; height:100%; z-index: 1;">
            <img src="https://davaksparfums.ch/wp-content/uploads/2026/01/exklusiv-parfum-kategorie.png" style="width:100%; height:100%; object-fit: cover; filter: brightness(0.4) contrast(1.1) grayscale(20%);" alt="Exclusive Selection">
        </div>
        
        <!-- Content -->
        <div class="hero-content" style="position: relative; z-index: 2; text-align: center; color: #fff; padding: 20px;">
            <h1 style="font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 300; letter-spacing: 6px; text-transform: uppercase; margin-bottom: 24px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">Exclusive Selection</h1>
            <p style="font-size: clamp(1rem, 2vw, 1.2rem); font-weight: 300; letter-spacing: 2px; color: #ddd; opacity: 0.9; text-shadow: 0 1px 5px rgba(0,0,0,0.5);">Eine Auswahl jenseits von Verfügbarkeit.</p>
        </div>
    </section>

    <!-- 2️⃣ BLOCK OF DELIMITATION (CLAVE) -->
    <section class="exclusive-definition" style="padding: 140px 20px; text-align: center; max-width: 680px; margin: 0 auto; background: #0b0b0b;">
         <!-- Placeholder Title -->
        <h2 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 3px; color: #555; margin-bottom: 40px;">Nicht alles gehört hierher.</h2>
        
        <!-- Placeholder Text based on "Rol" -->
        <p style="font-size: 1.4rem; line-height: 1.6; color: #d0d0d0; font-weight: 300; margin: 0;">
            Diese Auswahl ist bewusst begrenzt.<br>
            Nicht jeder Duft wird aufgenommen, und nicht jeder bleibt.<br>
            Hier entscheidet Substanz – nicht Nachfrage.
        </p>
    </section>

    <!-- 3️⃣ EDITORIAL BLOCK CENTRAL -->
    <section class="exclusive-editorial" style="margin-bottom: 140px; position: relative;">
        <!-- Horizontal Image -->
        <div class="editorial-img-container" style="height: 550px; overflow: hidden; position: relative;">
             <img src="https://davaksparfums.ch/wp-content/uploads/2026/01/imagen-seccion-4-v2.jpg" style="width:100%; height:100%; object-fit: cover; filter: grayscale(100%) brightness(0.6);" alt="Exclusive Editorial">
             <div class="editorial-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                <h3 style="color: #fff; font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 300; letter-spacing: 1px; text-align: center; padding: 20px; max-width: 800px; line-height: 1.4;">
                    Auswahl, die dem Markt nicht folgt.<br>Sondern dem eigenen Maßstab.
                </h3>
             </div>
        </div>
    </section>

    <!-- 4️⃣ GRID (VERY CONTROLLED) -->
    <section class="exclusive-grid" style="max-width: 1100px; margin: 0 auto 140px auto; padding: 0 40px;">
        <?php 
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 6, // Max 6 
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => 'exklusiv',
                ),
            ),
            'orderby' => 'date', 
            'order' => 'DESC' 
        );
        $loop = new WP_Query( $args );

        $microcopy_options = [
            "Still und konzentriert.",
            "Zurückhaltend mit Tiefe.",
            "Beständig und ruhig.",
            "Präzise im Ausdruck.",
            "Reduziert und klar."
        ];
        $m_count = 0;

        if ( $loop->have_posts() ) :
            echo '<ul class="products columns-3" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 80px 50px;">';
            while ( $loop->have_posts() ) : $loop->the_post();
                 global $product;
                 echo '<li class="product" style="text-align: center; list-style: none; padding: 0 !important; margin: 0 !important; background: transparent !important;">';
                 echo '<a href="' . get_permalink() . '" style="text-decoration: none;">';
                 
                 // Image
                 echo '<div class="prod-img" style="margin-bottom: 30px; opacity: 0.85; transition: opacity 0.4s ease;">';
                 echo $product->get_image( 'woocommerce_thumbnail', array('style' => 'display:block; margin: 0 auto; width: 100%; height: auto;') );
                 echo '</div>';

                 // Name
                 echo '<h2 style="font-size: 0.95rem; color: #eee; font-weight: 400; letter-spacing: 2px; margin-bottom: 10px; text-transform: uppercase; font-family: inherit;">' . get_the_title() . '</h2>';

                 // Price
                 echo '<div class="price" style="color: #666; font-size: 0.9rem; font-weight: 300; margin-bottom: 16px;">' . $product->get_price_html() . '</div>';

                 // Microcopy (Unique 1 line)
                 echo '<div class="microcopy" style="color: #444; font-size: 0.75rem; letter-spacing: 3px; text-transform: uppercase; opacity: 0.8;">' . $microcopy_options[$m_count % count($microcopy_options)] . '</div>';
                 
                 echo '</a>';
                 echo '</li>';
                 $m_count++;
            endwhile;
            echo '</ul>';
        else:
             echo '<p style="text-align: center; color: #555; font-style: italic; padding: 60px;">Die Sammlung ist derzeit geschlossen.</p>';
        endif;
        wp_reset_postdata();
        ?>
        
        <!-- Hover effect style -->
        <style>
            .exclusive-grid .prod-img:hover { opacity: 1 !important; }
        </style>
    </section>

    <!-- 5️⃣ CRITERION BLOCK (AFTER GRID) -->
    <section class="exclusive-criterion" style="text-align: center; max-width: 550px; margin: 0 auto 120px auto; padding: 0 20px;">
        <h3 style="font-size: 0.8rem; letter-spacing: 2px; color: #444; text-transform: uppercase; margin-bottom: 24px;">Auswahl mit Bestand</h3>
        <p style="color: #999; font-size: 1.1rem; line-height: 1.7; font-weight: 300;">
            Die Exclusive Selection folgt keinem festen Katalog.<br>
            Parfums erscheinen, bleiben oder verschwinden – wenn es angemessen ist.
        </p>
    </section>

    <!-- 6️⃣ TRUST (ULTRA DISCREET) -->
    <section class="exclusive-trust" style="text-align: center; margin-bottom: 100px; padding: 0 20px;">
         <p style="color: #333; font-size: 0.85rem; letter-spacing: 0.5px; line-height: 1.6; max-width: 600px; margin: 0 auto;">
            Auch in der Exclusive Selection gilt:
            geprüfte Originalware, zuverlässige Lieferung in der Schweiz und eine Auswahl mit klarem Qualitätsanspruch.
         </p>
    </section>

    <!-- 7️⃣ CLOSING (SILENCE) -->
    <section class="exclusive-closing" style="text-align: center; margin-bottom: 180px;">
        <p style="color: #333; font-size: 0.9rem; font-style: italic;">Nicht alles muss verfügbar sein.</p>
    </section>

</div>

<style>
    /* Global Overrides for this template */
    .site-content { background: #0b0b0b !important; margin: 0 !important; padding: 0 !important; border: none !important; }
    .ast-container { max-width: 100% !important; padding: 0 !important; border: none !important; }
    #primary, #main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    header.site-header { background-color: #0b0b0b !important; border-bottom: 1px solid #1a1a1a; }
    .site-footer { display: none !important; } /* As per requirement "No footer pesado inmediatamente debajo" - User said "No footer pesado... (deja espacio)". Maybe they mean standard footer is annoying. I will HIDE the standard footer and rely on the content, OR add a very large margin bottom before the footer appears. */
</style>
<!-- Re-enabling footer but with large spacing as per "deja espacio" -->
<style>
    .site-footer { display: block !important; margin-top: 0 !important; border-top: 1px solid #1a1a1a !important; background: #000 !important; }
</style>

<?php get_footer(); ?>