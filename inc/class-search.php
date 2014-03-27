<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class SellMediaSearch {

    /**
     * Init
     */
    public function __construct(){
        //add_action( 'pre_get_posts', array( &$this, 'get_orientation' ) );
        $this->includes();
    }

    /**
     * Include WP Advanced Search class files
     */
    private function includes(){
        require_once SELL_MEDIA_PLUGIN_DIR . '/inc/search/wpas.php';
    }

    /**
     * Search form arguments
     *
     * @since 1.8.7
     */
    public function args(){
        
        // setup our WP Advanced Search arguments
        $args = array();
        $args['wp_query'] = array(
            'post_type' => 'sell_media_item',
            'posts_per_page' => get_option( 'posts_per_page' ),
            'order' => 'DESC',
            'orderby' => 'date'
        );
        $args['fields'][] = array(
            'type' => 'search',
            'value' => '',
            'placeholder' => __( 'Search for something...', 'sell_media' )
        );
        $args['fields'][] = array(
            'type' => 'submit',
            'value' => 'Search'
        );
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'label' => 'Collections',
            'taxonomy' => 'collection',
            'format' => 'multi-select',
            'operator' => 'AND',
            'class' => 'chosen-select'
        );
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'label' => 'Keywords',
            'taxonomy' => 'keywords',
            'format' => 'multi-select',
            'operator' => 'AND',
            'class' => 'chosen-select'
        );
        $args['fields'][] = array(
            'type' => 'meta_key',
            'label' => 'Max Price',
            'meta_key' => 'sell_media_price',
            'values' => '',
            'data_type' => 'NUMERIC',
            'compare' => '<=',
            'format' => 'text',
            'placeholder' => __( 'Example: 100', 'sell_media' )
        );
        $args['fields'][] = array(
            'type' => 'html',
            'value' => '<div id="sell-media-toggle-search-options"><a href="javascript:void(0);">' . __( 'Close', 'sell_media' ) . '</a></div>'
        );

        return $args;
    }

    /**
     * Search form
     *
     * @since 1.8.7
     */
    public function form( $url=null ){

        // enqueue chosen scripts
        wp_enqueue_script( 'sell_media-chosen' );
        wp_enqueue_style( 'sell_media-chosen' );

        $args = $this->args();

        // allow form to post to a custom url
        if ( ! empty ( $url ) ) {
            $new_args['form'] = array(
                'action' => esc_url( $url ),
                'method' => 'GET',
                'id' => 'wp-advanced-search',
                'name' => 'wp-advanced-search',
                'class' => 'wp-advanced-search'
            );

            $args = array_merge( $args, $new_args );
        }

        $sell_media_search_object = new WP_Advanced_Search( $args );

        echo '<div class="sell-media-search cf">';
        $sell_media_search_object->the_form();
        echo '</div>';
    }

    /**
     * Search results
     *
     * @since 1.8.7
     */
    public function results(){

        global $wp_query, $post;

        $args = $this->args();
        $sell_media_search_object = new WP_Advanced_Search( $args );

        $temp_query = $wp_query;
        $wp_query = $sell_media_search_object->query();

        echo '<div id="sell-media-archive" class="sell-media">';

        if ( have_posts() ) :

            echo '<p class="sell-media-search-results-total">' . __( 'Displaying results ', 'sell_media' ) . $sell_media_search_object->results_range() . __( ' of ', 'sell_media' ) . $wp_query->found_posts . '</p>';
            
            echo '<div class="sell-media-grid-container">';
            $i = 0;
            while ( have_posts() ) : the_post(); $i++;
            ?>
                <div class="sell-media-grid<?php if ( $i %3 == 0 ) echo ' end'; ?>">
                    <div class="sell-media-item-details-inner">
                        <a href="<?php the_permalink(); ?>"><?php sell_media_item_icon( $post->ID ); ?></a>
                        <span class="view-overlay">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <?php sell_media_item_buy_button( $post->ID, 'text', __( 'Purchase', 'sell_media' ) ); ?>
                        </span>
                    </div>
                </div>
            <?php

            endwhile;
            echo '</div>';
            $i = 0;
            $sell_media_search_object->pagination();

        else :

            _e( 'Sorry, no results. Try broadening your search.', 'sell_media' );

        endif;

        echo '</div>';
        $wp_query = $temp_query;
        wp_reset_query();

    }


    /**
     * Filters search results based on aspect ratio
     *
     * @param $posts (int)
     * @param $orientation (string) any|landscape|portrait
     *
     * @return Array of post IDs that are either landscape or portrait
     */
    public function get_orientation( $query ){

        if ( ! is_admin() && ! empty( $_GET['wpas'] ) && ( $_GET['orientation'] == 'landscape' || $_GET['orientation'] == 'portrait' ) ) {

            $orientation = get_query_var( 'orientation' );
            $post_ids = Sell_Media()->images->get_posts_by_orientation( $orientation );
            $query->set( 'post__in', $post_ids );
            
        }
    }


}