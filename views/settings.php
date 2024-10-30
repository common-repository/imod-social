<?php
require_once(__DIR__ . '/../includes/Imod_SocialFetcher.php');

function imod_social_page_tabs($current = 'updates')
{
    $tabs = array(
        'updates' => 'General',
        'scoring' => 'Scoring',
    );
    $html = '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $name) {
        $class = ($tab == $current) ? 'nav-tab-active' : '';
        $html .= '<a class="nav-tab ' . $class . '" href="?page=wp-social&tab=' . $tab . '">' . $name . '</a>';
    }
    $html .= '</h2>';
    echo $html;
}

function imod_social_post_performance_view()
{
    ?>
    <div class="wrap wpsocial-wrapper">
        <div class="wpsocial-main">
            <h1>Settings</h1>
            <?php
            settings_errors();

            $tab = (!empty($_GET['tab'])) ? esc_attr($_GET['tab']) : 'updates';
            imod_social_page_tabs($tab);
            switch ($tab) {
                case 'scoring': {
                    imod_social_scoring();
                    break;
                }
                case 'updates': {
                    imod_social_updates();
                    break;
                }
            }
            ?>
        </div>
        <?php require 'sidebar.php'; ?>
    </div>
    <?php
}

function imod_social_scoring()
{
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields('isig:scores_group');
        ?>
        <p>When you look at your 'Posts > All Posts', you will notice little icons that represent how well the post is
            doing. A green icon represents a post that is performing really well, an orange icon represents a post that
            is doing fairly well and a red icon represents a post that is not doing well.</p>
        <p>Every website is different, therefore we're giving you the flexibility to define what is 'Good', 'Fair' and 'Bad'.
            If you think that having your post shared 15 times or more across social networks is 'Good', then you enter
            "15" into the first block below. Next, set up the score requirements for 'Fair' and 'Bad'.</p>
        <h2>Settings</h2>
        <?php
        do_settings_sections('score_tab');
        submit_button();
        ?>
    </form>
    <?php
}

function imod_social_updates()
{
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields('isig:updates_group');
        ?>
        <p>The plugin monitors social media websites to determine the success of your posts. Since this may affect the speed of your website, we have provided some settings below. If you have a fast website with a good hosting provider, you can set the 'Update Frequency' to  'Hourly' as well as increase the 'Number of Posts'. However, if your website runs slowly, it would be recommended to set the 'Update Frequency' to 'Daily' and the 'Number of Posts' to '10'.</p>
        <p>Recommended Settings: Update Frequency: Daily. Number of Posts: 10.</p>
        <h2>Settings</h2>
        <?php
        do_settings_sections('updates_tab');
        submit_button();
        ?>
    </form>
    <?php
}