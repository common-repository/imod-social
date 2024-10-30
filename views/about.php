<?php
function imod_social_about_view()
{
    ?>
    <div class="wrap wpsocial-wrapper">
        <div class="wpsocial-main">
            <h1>About WordPress Social</h1>
            <p>Having worked extensively with WordPress, there has never been a way to see how well posts are performing on social media websites. We set out to create a plugin that would assist bloggers and content marketers in understanding how often their posts were being shared on social media websites.</p>
            <p>This is what you will see in 'Posts > All Posts':</p>
            <p><img src="<?php echo plugins_url('assets/example.png', dirname(__FILE__)) ?>"></p>
            <p>Brought to you by the team at <a href="http://www.imoddigital.com" target="_blank">iMod Digital</a>.</p>
        </div>
        <?php require 'sidebar.php'; ?>
    </div>
    <?php
}