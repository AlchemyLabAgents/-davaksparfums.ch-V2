<?php
/**
 * The template for displaying Herren (Men's) Product Category
 *
 * @package Astra Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header(); 

// Get current category term
$term = get_queried_object();
$term_id = $term->term_id;

// Get image URL (using the ID we just set)
$thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );
$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );

// Fallback image if none set
if ( ! $image_url ) {
    $image_url = 'https://via.placeholder.com/1920x800?text=Davaks+Herren';
}
?>

<div id="primary" class="content-area primary" style="background-color: #0b0b0b; color: #e5e5e5;">
	<main id="main" class="site-main">

		<!-- 1️⃣ HERO DE CATEGORÍA -->
		<section class="davaks-category-hero" style="position: relative; height: 75vh; min-height: 500px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
			<?php if ( $image_url ) : ?>
				<div class="davaks-hero-bg" style="position: absolute; top:0; left:0; width: 100%; height: 100%; z-index: 1;">
					<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
					<div class="davaks-hero-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.6));"></div>
				</div>
			<?php endif; ?>
			
			<div class="davaks-hero-content" style="position: relative; z-index: 2; text-align: center; max-width: 900px; padding: 20px;">
				<h1 style="color: #fff; font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1.5rem; font-weight: 400; letter-spacing: 2px; text-transform: uppercase;">Herrenparfums</h1>
				<p style="color: #f5f5f5; font-size: clamp(1.1rem, 2vw, 1.4rem); font-weight: 300; letter-spacing: 0.5px; opacity: 0.9;">Düfte mit Klarheit, Tiefe und Präsenz.</p>
			</div>
		</section>

		<div class="davaks-container" style="max-width: 1300px; margin: 0 auto; padding: 0 20px;">

			<!-- 2️⃣ BLOQUE DE CRITERIO -->
			<section class="davaks-criterio" style="padding: 100px 0; text-align: center; max-width: 760px; margin: 0 auto;">
				<h2 style="font-size: 1.1rem; text-transform: uppercase; letter-spacing: 3px; color: #888; margin-bottom: 24px;">Auswahl mit Haltung</h2>
				<p style="font-size: 1.5rem; line-height: 1.6; color: #e5e5e5; font-weight: 300;">
					Diese Kollektion richtet sich an Männer mit einem klaren Stilverständnis.
					Jeder Duft wurde bewusst ausgewählt – nach Ausdruck, Balance und Beständigkeit.
					Nicht laut, nicht flüchtig. Sondern präsent.
				</p>
			</section>

			<!-- 3️⃣ FILTRO / ORDENACIÓN -->
			<section class="davaks-filters" style="margin-bottom: 60px; border-bottom: 1px solid #222; padding-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div class="filter-group" style="display: flex; gap: 12px; align-items: center;">
                    <span style="color: #666; font-size: 0.9rem; margin-right: 8px;">Filtern:</span>
                    <div class="filter-pill" style="border: 1px solid #444; color: #ccc; padding: 6px 16px; border-radius: 20px; font-size: 0.9rem; cursor: pointer;">Intensität</div>
                    <div class="filter-pill" style="border: 1px solid #444; color: #ccc; padding: 6px 16px; border-radius: 20px; font-size: 0.9rem; cursor: pointer;">Anlass</div>
                    <div class="filter-pill" style="border: 1px solid #444; color: #ccc; padding: 6px 16px; border-radius: 20px; font-size: 0.9rem; cursor: pointer;">Duftcharakter</div>
                </div>
				<div class="sort-group">
                    <form class="woocommerce-ordering" method="get">
                        <select name="orderby" class="orderby" aria-label="Shop-Bestellung" style="background: transparent; color: #ccc; border: none; font-size: 0.95rem; cursor: pointer;">
                            <option value="menu_order" selected="selected">Sortierung: Empfehlung</option>
                            <option value="popularity">Auswahlfavoriten</option>
                            <option value="date">Neu in der Auswahl</option>
                            <option value="price">Preis: aufsteigend</option>
                            <option value="price-desc">Preis: absteigend</option>
                        </select>
                        <input type="hidden" name="paged" value="1">
                    </form>
				</div>
			</section>

			<!-- 4️⃣ GRID DE PRODUCTOS (Primary: First 8) -->
			<section class="davaks-product-grid" style="margin-bottom: 100px;">
				<?php 
                    $args1 = array(
                        'post_type' => 'product',
                        'posts_per_page' => 8,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => 'herren-2', // Adjust slug if necessary (trying 'herren' and 'herren-2')
                            ),
                        ),
                    );
                    // Try slug 'herren' first
                    if( ! term_exists('herren-2', 'product_cat') ) {
                         $args1['tax_query'][0]['terms'] = 'herren';
                    }
                    
                    $loop1 = new WP_Query( $args1 );
                    
                    if ( $loop1->have_posts() ) :
                        echo '<ul class="products columns-4" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 40px;">';
                        while ( $loop1->have_posts() ) : $loop1->the_post();
                            // Standard WC Template Part
                            wc_get_template_part( 'content', 'product' );
                        endwhile;
                        echo '</ul>';
                    else:
                        echo '<p style="text-align:center; padding: 40px; color: #888;">Keine Produkte gefunden.</p>';
                    endif;
                    wp_reset_postdata();
                ?>
                <!-- Style override for this page specifically to enforce clean look -->
                <style>
                    .davaks-container { width: 100%; }
                    .davaks-product-grid ul.products,
                    .davaks-product-grid-2 ul.products { list-style: none; padding: 0; margin: 0; }
                    .davaks-product-grid ul.products li.product,
                    .davaks-product-grid-2 ul.products li.product { background: transparent !important; text-align: left; width: 100%; }
                    .davaks-product-grid ul.products li.product a img,
                    .davaks-product-grid-2 ul.products li.product a img { width: 100%; height: auto; max-width: 100%; margin-bottom: 20px; opacity: 0.95; transition: opacity 0.3s; display: block; }
                    .davaks-product-grid ul.products li.product a img:hover { opacity: 1; }
                    .davaks-product-grid .woocommerce-loop-product__title,
                    .davaks-product-grid-2 .woocommerce-loop-product__title { font-size: 1.1rem !important; color: #fff !important; font-weight: 500 !important; letter-spacing: 0.5px; margin-bottom: 6px; }
                    .davaks-product-grid .price,
                    .davaks-product-grid-2 .price { color: #aaa !important; font-size: 0.95rem !important; font-weight: 300; }
                    .davaks-product-grid .button, .davaks-product-grid .added_to_cart,
                    .davaks-product-grid-2 .button, .davaks-product-grid-2 .added_to_cart { display: none; }
                </style>
			</section>

			<!-- 5️⃣ BLOQUE EDITORIAL INTERMEDIO -->
            <section class="davaks-editorial" style="background: #141414; padding: 0; margin-bottom: 100px; display: grid; grid-template-columns: 1fr 1fr; gap: 0px; align-items: stretch; min-height: 450px;">
                <div class="editorial-text" style="padding: 80px 60px; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-size: 0.85rem; letter-spacing: 2px; text-transform: uppercase; color: #777; margin-bottom: 12px;">Editorial</span>
                    <h3 style="color: #fff; margin-bottom: 24px; font-size: 2.2rem; font-weight: 400; line-height: 1.2;">Ein Duft begleitet nicht.<br>Er definiert Präsenz.</h3>
                    <p style="color: #bbb; font-size: 1.1rem; line-height: 1.6; max-width: 450px;">Maskuline Eleganz zeigt sich in Klarheit. Diese Kollektion setzt auf Ruhe, Tiefe und strukturierte Kompositionen – ohne Lautstärke.</p>
                </div>
                <div class="editorial-image" style="background-image: url('https://davaksparfums.ch/wp-content/uploads/2026/01/imagen-seccion-4-v2.jpg'); background-size: cover; background-position: center; min-height: 300px; background-color: #222;">
                    <!-- Image placeholder from library or generic -->
                    <img src="https://davaksparfums.ch/wp-content/uploads/2026/01/imagen-seccion-4-v2.jpg" style="width:100%; height:100%; object-fit:cover; display:block; opacity:0;" alt="Editorial Herren">
                </div>
            </section>

            <!-- 5.5 Mobile CSS for Editorial -->
            <style>
                @media(max-width: 768px) {
                    .davaks-editorial { grid-template-columns: 1fr !important; }
                    .editorial-text { padding: 40px 20px !important; }
                    .editorial-image { min-height: 250px; }
                    .davaks-hero-content h1 { font-size: 2.5rem !important; }
                    .davaks-filters { justify-content: flex-start; }
                }
            </style>

			<!-- 6️⃣ SEGUNDO GRID (Remaining) -->
			<section class="davaks-product-grid-2" style="margin-bottom: 120px;">
                <?php 
                    $args2 = array(
                        'post_type' => 'product',
                        'posts_per_page' => 12, // Load more
                        'offset' => 8,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => 'herren-2',
                            ),
                        ),
                    );
                     if( ! term_exists('herren-2', 'product_cat') ) {
                         $args2['tax_query'][0]['terms'] = 'herren';
                    }

                    $loop2 = new WP_Query( $args2 );
                    
                    if ( $loop2->have_posts() ) :
                        echo '<h4 style="color: #666; text-transform: uppercase; letter-spacing: 2px; font-size: 0.9rem; margin-bottom: 40px; text-align: center;">Weitere Entdeckungen</h4>';
                        echo '<ul class="products columns-4" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 40px;">';
                        while ( $loop2->have_posts() ) : $loop2->the_post();
                            wc_get_template_part( 'content', 'product' );
                        endwhile;
                        echo '</ul>';
                    endif;
                    wp_reset_postdata();
                ?>
			</section>

			<!-- 7️⃣ BLOQUE DE CONFIANZA -->
			<section class="davaks-trust" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 100px; border-top: 1px solid #1a1a1a; padding-top: 80px; text-align: center;">
				<div class="trust-item">
					<h4 style="color: #fff; margin-bottom: 12px; font-weight: 500; letter-spacing: 0.5px;">Geprüfte Originalware</h4>
				</div>
				<div class="trust-item">
					<h4 style="color: #fff; margin-bottom: 12px; font-weight: 500; letter-spacing: 0.5px;">Zuverlässige Lieferung in der Schweiz</h4>
				</div>
				<div class="trust-item">
					<h4 style="color: #fff; margin-bottom: 12px; font-weight: 500; letter-spacing: 0.5px;">Sorgfältige Auswahl mit klarem Qualitätsanspruch</h4>
				</div>
			</section>

			<!-- 8️⃣ FAQ DE CATEGORÍA -->
            <section class="faq-section davaks-cat-faq" style="margin: 0 auto 100px auto;">
                <h3>Häufige Fragen zu Herrenparfums</h3>
                <div class="faq-accordion" itemscope itemtype="https://schema.org/FAQPage">
                    <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                        <summary itemprop="name">Für welchen Typ Mann sind diese Düfte?</summary>
                        <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                            <p itemprop="text">Für Männer, die Zurückhaltung mit Präsenz verbinden und Wert auf klaren Stil legen.</p>
                        </div>
                    </details>
                    <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                        <summary itemprop="name">Sind die Düfte für den Alltag oder besondere Anlässe gedacht?</summary>
                        <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                            <p itemprop="text">Beides. Die Kollektion umfasst zurückhaltende wie auch präsente Kompositionen.</p>
                        </div>
                    </details>
                    <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                        <summary itemprop="name">Wie intensiv sind die Herrenparfums?</summary>
                        <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                            <p itemprop="text">Die Auswahl reicht von dezent bis ausdrucksstark – mit Fokus auf Balance und Haltbarkeit.</p>
                        </div>
                    </details>
                </div>
            </section>
			
            <!-- 9️⃣ CIERRE SUAVE -->
			<section class="davaks-closing" style="text-align: center; padding-bottom: 100px; color: #555; font-style: italic;">
				<p>Herrenparfums für Männer mit Haltung, Ruhe und Klarheit.</p>
			</section>

		</div><!-- .davaks-container -->
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>