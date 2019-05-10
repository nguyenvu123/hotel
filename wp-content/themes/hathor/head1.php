
<div id="branding">
 <div class="row">
 <div class="large-12 columns">



    	<!--LOGO START-->
        <div id="site-title">
          <?php $hathor_logo_image = of_get_option('hathor_logo_image'); ?>

          <?php if (!empty( $hathor_logo_image )) : ?>

        <?php if (of_get_option('hathor_logo_image')) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo-img"><img src="<?php echo of_get_option('hathor_logo_image'); ?>" alt="<?php bloginfo( 'name' ); ?>" /></a>
                    <?php else : ?>

       <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?> - <?php bloginfo('description'); ?>" rel="home"><?php bloginfo('name'); ?></a>
     <?php endif; ?>
   <?php else : ?>

     <?php if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) : ?>
       <?php hathor_the_custom_logo(); ?>
     <?php else : ?>
       <h1 class="site-title">
         <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
       </h1>
     <?php endif;?>
   <?php endif; ?>

        </div>


        <!--LOGO END-->
         <h3 class="menu-toggle"><?php _e( 'Menu', 'hathor' ); ?></h3>

        <!--MENU STARTS-->


        <div id="menu_wrap">


        <div id="navmenu"><?php wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>   </div>

        </div>



   </div>

      </div>


</div>
             <!--MENU END-->
