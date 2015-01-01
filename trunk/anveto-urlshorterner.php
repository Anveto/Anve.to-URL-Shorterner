<?php

/*
Plugin Name: Anveto URL Shorterner
Plugin URI: http://anve.to
Description: Shorten and track statistics of your outgoing urls automatically with Anve.to
Version: 1.0
Author: Anveto, Markus Tenghamn
Author URI: http://anveto.com
License: GPL2
*/

add_action('admin_menu', 'anveto_shortenerMenu');

add_filter('edit_post_content', 'anveto_generateShortUrls', 10, 2);

function anveto_shortenURL($url, $api)
{
    //Todo, a custom alias could be set here depending on the user or id of the link
    $apikey = $api;
    $api_url = "http://anve.to/short/api?api=" . $apikey . "&url=" . $url;
    $res = @json_decode(file_get_contents($api_url), TRUE);
    if ($res["error"]) {
        return "";
    } else {
        return $res["short"];
    }
}

function anveto_getUrls($string)
{
    $string = '<xmlcontent>'.$string.'</xmlcontent>';
    $xml = simplexml_load_string($string);
    $list = $xml->xpath("//@href");
    $urls = array();
    foreach ($list as $l) {
        $arr = $l['href'];
        $urls[] = trim(str_replace('href=',"",str_replace('"',"",$arr->asXML())));
    }
    return $urls;
}

//We modify the post finding all external urls and shortening them. Then we replace those urls with their respective short urls.
function anveto_generateShortUrls($content)
{
    $apikey = get_option('anveto-urlshorternerKey');
    $shorteninternal = get_option('anveto-shortenInternalLinks');

    $ignore = array("anve.to");

    if ($shorteninternal != "internalLinks") {
        $domain_name = preg_replace('/^www\./', '', $_SERVER['SERVER_NAME']);
        $ignore[] = $domain_name;
    }

    if (strlen($apikey) > 1) {
        $urls = anveto_getUrls($content);

        foreach ($urls as $url) {
            $found = false;
            foreach ($ignore as $ign) {
                if (strpos($url, $ign) !== false) {
                    $found = true;
                }
            }
            if (!$found) {
                $shorturl = anveto_shortenURL($url, $apikey);
                if (strlen($shorturl) > 1) {
                    $content = str_replace("href=\"".$url, "href=\"".$shorturl, $content);
                }
            }
        }
    }
    return $content;
}

function anveto_shortenerMenu()
{
    add_menu_page('Anve.to URL Shorterner', 'Anve.to', 'administrator', __FILE__, 'anveto_settingsPage', plugins_url('/images/icon.png', __FILE__));

    add_action('admin_init', 'Anveto_registerSettings');
}

function Anveto_registerSettings()
{
    register_setting('anveto-settingsGroup', 'anveto-urlshorternerKey');
    register_setting('anveto-settingsGroup', 'anveto-shortenInternalLinks');
}

function anveto_settingsPage()
{
    ?>
    <div class="wrap">
        <h2>Anve.to URL Shorterner</h2>

        <p>
            This plugin will automatically create short urls for all of your outgoing links when you create or edit a
            post.
            If you have an older post that you would like to shorten your links for then simply edit the post and it
            should update the links.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('anveto-settingsGroup'); ?>
            <?php do_settings_sections('anveto-settingsGroup'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Anve.to API Key (<a href="http://anve.to">Register for a free account</a>)</th>
                    <td><input type="text" name="anveto-urlshorternerKey"
                               value="<?php echo get_option('anveto-urlshorternerKey'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Also shorten internal urls?</th>
                    <td><input type="checkbox" name="anveto-shortenInternalLinks"
                               value="internalLinks" <?php if (get_option('anveto-shortenInternalLinks') == 'internalLinks') {
                            echo 'CHECKED';
                        } ?>/>
                    </td>
                </tr>

            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php
}

