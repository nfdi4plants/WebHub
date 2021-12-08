<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

defined('_HZEXEC_') or die();

$filters = array();
$filters['params'] = App::get('menu.params');
$filters['start'] = 0;
$filters['category_id'] = 27;
$filters['context']   = 'com_content.category';
$filters['published'] = array(Components\Content\Models\Article::STATE_PUBLISHED);
$filters['access'] = User::getAuthorisedViewLevels();
$filters['ordering'] = 'a.publish_up';
$filters['direction'] = 'DESC';
$filters['language'] = App::get('language.filter');

$categories = \Components\Categories\Helpers\Categories::getInstance('Content');
$category = $categories->get($filters['category_id']);

$query = Components\Content\Models\Article::AllByFilters($filters);

$articles = $query
    ->order('a.created', 'DESC')
    ->start($filters['start'])
    ->rows()
    ->toArray();

$i = -1;

foreach ($articles as $article) {
    $i++;
    $i %= 4;
    $color = "undefined";
    switch ($i) {
        case 0:
            $color = "mint";
            break;
        case 2:
            $color = "darkblue";
            break;
        case 1:
            $color = "lightblue";
            break;
        case 3:
            $color = "lightgray";
            break;
    }

    $imgobj = json_decode($article['images']);
    $urlobj = json_decode($article['urls']);
?>

<div class="module-landing">
<!-- start landing slides -->
    <!-- ARC -->
    <div class="container-md">
        <div class="row hero bg-<?php echo $color ?>">
            <div class="hero-image <?php if ($i % 2 == 1) {
                                        echo "order-1 order-md-2";
                                    } ?>">
                <object class="landimages" data="<?php echo $imgobj->image_fulltext ?>" alt="<?php $imgobj->image_fulltext_alt ?>" type="image/svg+xml"></object>
            </div>
            <div class="hero-content <?php if ($i % 2 == 1) {
                                            echo "order-2 order-md-1";
                                        } ?>">
                <div class="card bg-white">
                    <div class="card-body">
                        <h5 class="text<?php echo $color ?> card-title upcase"><?php echo $article['alias'] ?></h5>
                        <h2 class="card-title"><?php echo $article['title'] ?></h2>
                        <p class="card-text"><?php echo $article['introtext'] ?></p>
                        <?php if (!$urlobj->urla)
                            goto endlink; ?>
                        <a href="<?php echo $urlobj->urla ?>" class="learn-more"><?php echo $urlobj->urlatext ?></a>
                        <?php endlink: ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
<!-- end landing slides -->

<!-- news preview -->
<div class="news-container">
    <h2 class="news-header">News</h2>
    <div class="news">
        <?php
        $filters = array();
        $filters['params'] = App::get('menu.params');
        $filters['start'] = 0;
        // hard code for now
        $filters['category_id'] = 26; // News - Could possibly be fetched by name instead
        $filters['context']   = 'com_content.category';
        $filters['published'] = array(Components\Content\Models\Article::STATE_PUBLISHED);
        $filters['access'] = User::getAuthorisedViewLevels();
        $filters['ordering'] = 'a.publish_up';
        $filters['direction'] = 'DESC';
        $filters['language'] = App::get('language.filter');
        $filters['limit'] = 4;

        $categories = \Components\Categories\Helpers\Categories::getInstance('Content');
        $category = $categories->get($filters['category_id']);

        $query = Components\Content\Models\Article::AllByFilters($filters);

        $articles = $query
            ->order($filters['ordering'], $filters['direction'])
            ->start($filters['start'])
            ->limit($filters['limit'])
            ->rows()
            ->toArray();

        foreach ($articles as $article) {
        ?>
            <div class="article">
                <h3 class="title"><?php echo $article['title'] ?></h3>
                <b class="date"><?php echo Lang::txt('COM_CONTENT_PUBLISHED_DATE_ON', Date::of($article['publish_up'])->toLocal(Lang::txt('DATE_FORMAT_LC1'))); ?></b>
                <div class="text">
                <?php
                    $limit = 475;
                    $text = strip_tags($article['introtext']);
                    if (strlen($text) > $limit) {
                        $wordEnd = strpos($text, ' ', $limit);
                        $wordEnd = $wordEnd > $limit ? $wordEnd : $limit;
                        $text = substr($text, 0, $wordEnd) . ' ...';
                    } else {
                        if (!empty($text)) {
                            $text .= ' ...';
                        }
                    }
                    echo $text;
                    $article['slug'] = $article['alias'] ? ($article['alias'] . ':' . $article['id']) : $article['id'];
                ?>
                </div>
            <a href="<?php echo Route::url(Components\Content\Site\Helpers\Route::getArticleRoute($article['slug'], $article['catid'], $article['language'])); ?>" class="btn-read-more">Read more</a>
            </div>
        <?php } ?>
    </div>
    <a class="learn-more" href="/news">More news</a>
</div>
<!-- end news preview -->
</div>