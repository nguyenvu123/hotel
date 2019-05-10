
<div class="row">


 <div id="branding2">


    	<h3 class="menu-toggle"><?php _e( 'Menu', 'hathor' ); ?></h3><!--LOGO START-->
      <?php $hathor_logo_image = of_get_option('hathor_logo_image'); ?>

      <?php if (!empty( $hathor_logo_image )) : ?>

        <div id="site-title2">

        <?php if (of_get_option('hathor_logo_image')) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo-img"><img src="<?php echo of_get_option('hathor_logo_image'); ?>" alt="<?php bloginfo( 'name' ); ?>" /></a>
                    <?php else : ?>

       <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?> - <?php bloginfo('description'); ?>" rel="home"><?php bloginfo('name'); ?></a><?php endif; ?>


        </div>
        <div class="desc"><?php bloginfo('description'); ?></div>
      <?php else : ?>
        <div id="site-title2">
        <?php if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) : ?>
          <?php hathor_the_custom_logo(); ?>
        <?php else : ?>
          <h1 class="site-title">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
          </h1>
            </div>
          <?php
            $description = get_bloginfo( 'description', 'display' );
            if ( $description || is_customize_preview() ) : ?>
              <div class="desc"><p class="site-description"><?php echo $description; /* WPCS: xss ok. */ ?></p></div>
            <?php endif; ?>
        <?php endif;?>
      <?php endif; ?>

        </div>

       </div>




        <!--LOGO END-->

        <!--MENU STARTS-->

        <div class="row">



         <div id="menu_wrap2">
         <div id="navmenu"><?php wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>   </div>

        </div>


        </div>


        </div></div>
        <!--MENU END-->
