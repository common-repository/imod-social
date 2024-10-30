<?php
function imod_social_export_view()
{
    ?>
    <div class="wrap wpsocial-wrapper">
        <div class="wpsocial-main">
            <h1>Export</h1>
            <p>Export a list of your posts with their social performance metrics. Download a list of your best performing posts and easily determine what sort of content your audience is most interested in.</p>
            <form method="post">
                <?php wp_nonce_field('isig', 'isig_nounce'); ?>
                <table class="form-table">

                    <tr>
                        <th scope="row"><label for="default_role">Performance:</label></th>
                        <td>
                            <select name="status">
                                <option value="0">Bad</option>
                                <option value="1">Fair</option>
                                <option value="2">Good</option>
                                <option value="3">ALL</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Export'); ?>
            </form>
        </div>
        <?php require 'sidebar.php'; ?>
    </div>
<?php }