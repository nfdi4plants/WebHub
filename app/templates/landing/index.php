<?php

/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

defined('_HZEXEC_') or die();
Html::behavior('framework', true);
Html::behavior('modal');

// Include global scripts
$this->addScript($this->baseurl . '/templates/' . $this->template . '/js/hub.js?v=' . filemtime(__DIR__ . '/js/hub.js'));

// Load theme
$color1   = str_replace('#', '', $this->params->get('colorPrimary', '2f8dc9')); // 2f8dc9  171a1f
$opacity1 = $this->params->get('colorPrimaryOpacity', '');
$color2   = str_replace('#', '', $this->params->get('colorSecondary', '2f8dc9'));
$opacity2 = $this->params->get('colorSecondaryOpacity', '');
$bground  = $this->params->get('backgroundImage', $this->params->get('background', 'delauney'));

$hash = md5($color1 . $bground . $color2);
$p = substr(PATH_APP, strlen(PATH_ROOT));
$path = '/templates/' . $this->template . '/css/theme.php?path=' . urlencode($p) . '&c1=' . urlencode($color1) . '&c2=' . urlencode($color2) . '&bg=' . urlencode($bground) . ($opacity1 ? '&o1=' . $opacity1 : '') . ($opacity2 ? '&o2=' . $opacity2 : '');
if (file_exists(PATH_APP . '/cache/site/' . $hash . '.css')) {
	$path = '/cache/site/' . $hash . '.css';
}

$this->addStyleSheet($this->baseurl . $path);

// Get browser info to set some classes
$menu = App::get('menu');
$browser = new \Hubzero\Browser\Detector();
$cls = array(
	'no-js',
	$browser->name(),
	$browser->name() . $browser->major(),
	$this->direction,
	$this->params->get('header', 'light'),
	($menu->getActive() == $menu->getDefault() ? 'home' : '')
);

// Prepend site name to document title
$this->setTitle(Config::get('sitename') . ' - ' . $this->getTitle());
?>
<!DOCTYPE html>
<html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="<?php echo implode(' ', $cls); ?>">

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="/core/assets/css/fontcons.css" />
	<link rel="stylesheet" href="<?php echo $this->baseurl . '/templates/' . $this->template; ?>/css/main.css" />
	<link rel="stylesheet" type="text/css" media="all" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/index.css?v=<?php echo filemtime(__DIR__ . '/css/index.css'); ?>" />

	<jdoc:include type="head" />

	<!--[if IE 9]>
			<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie9.css" />
		<![endif]-->
	<!--[if lt IE 9]>
			<script type="text/javascript" src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js"></script>
			<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie8.css" />
		<![endif]-->
</head>

<body>
	<div id="outer-wrap">
		<jdoc:include type="modules" name="helppane" />

		<div id="top">
			<div id="splash">
				<div class="inner-wrap">

					<header id="masthead">
						<jdoc:include type="modules" name="notices" />

						<h1>
							<a href="<?php echo Request::root(); ?>" title="<?php echo Config::get('sitename'); ?>">
								<span><img src="/img/logo.svg" height=48px alt="logo" /> </span>
							</a>
						</h1>

						<nav id="account" class="account-navigation">
							<ul>
								<li>
									<a class="icon-search" href="<?php echo Route::url('index.php?option=com_search'); ?>" title="<?php echo Lang::txt('TPL_LANDING_SEARCH'); ?>"><?php echo Lang::txt('TPL_LANDING_SEARCH'); ?></a>
									<jdoc:include type="modules" name="search" />
								</li>
								<?php if (!User::isGuest()) { ?>
									<li class="user-account loggedin <?php if (User::authorise('core.admin')) {
																			echo ' admin';
																		} ?>">
										<a class="user-avatar" href="<?php echo Route::url(User::link()); ?>">
											<img src="<?php echo User::picture(); ?>" alt="<?php echo User::get('name'); ?>" width="30" height="30" />
										</a>
										<?php if (User::authorise('core.admin')) { ?>
											<span><a class="icon-star user-account-badge tooltips" href="<?php echo Request::root() . 'administrator'; ?>" title="<?php echo Lang::txt('TPL_LANDING_ACCOUNT_VIEWING_AS_ADMIN'); ?>"><?php echo Lang::txt('TPL_LANDING_ACCOUNT_ADMIN'); ?></a></span>
										<?php } ?>
										<div class="user-account-options">
											<div class="user-account-details">
												<span class="user-account-name"><?php echo stripslashes(User::get('name')); ?></span>
												<span class="user-account-email"><?php echo User::get('email'); ?></span>
											</div>
											<ul>
												<li>
													<a class="icon-th-large" href="<?php echo Route::url(User::link() . '&active=dashboard'); ?>"><span><?php echo Lang::txt('TPL_LANDING_ACCOUNT_DASHBOARD'); ?></span></a>
												</li>
												<li>
													<a class="icon-user" href="<?php echo Route::url(User::link() . '&active=profile'); ?>"><span><?php echo Lang::txt('TPL_LANDING_ACCOUNT_PROFILE'); ?></span></a>
												</li>
												<li>
													<a class="icon-logout" href="<?php echo Route::url('index.php?option=com_users&view=logout'); ?>"><span><?php echo Lang::txt('TPL_LANDING_LOGOUT'); ?></span></a>
												</li>
											</ul>
										</div>
									</li>
								<?php } else { ?>
									<li class="user-account loggedout">
										<a class="icon-login" href="<?php echo Route::url('index.php?option=com_users&view=login'); ?>" title="<?php echo Lang::txt('TPL_LANDING_LOGIN'); ?>"><?php echo Lang::txt('TPL_LANDING_LOGIN'); ?></a>
									</li>
									<?php if ($this->params->get('registerLink') && Component::params('com_members')->get('allowUserRegistration')) : ?>
										<li class="user-account-create">
											<a class="icon-register" href="<?php echo Route::url('index.php?option=com_register'); ?>" title="<?php echo Lang::txt('TPL_LANDING_SIGN_UP'); ?>"><?php echo Lang::txt('TPL_LANDING_REGISTER'); ?></a>
										</li>
									<?php endif; ?>
								<?php } ?>
							</ul>
						</nav>

						<nav id="nav" class="main-navigation" aria-label="<?php echo Lang::txt('TPL_LANDING_MAINMENU'); ?>">
							<ul id="external">
								<li><a href="https://twitter.com/nfdi4plants"><img src="/third_party_logos/2021_twitter_logo_white.png"></a></li>
								<li><a href="https://github.com/nfdi4plants"><img src="/third_party_logos/GitHub-Mark-Light-120px-plus.png"></a></li>
								<!-- <li><a href="https://zenodo.org/communities/nfdi4plants"><img src=""></a>Z</li> -->
							</ul>
							<jdoc:include type="modules" name="user3" />
						</nav>
					</header>


					<div class="inner">
						<div class="wrap">
							<?php if ($this->getBuffer('message')) : ?>
								<jdoc:include type="message" />
							<?php endif; ?>
							<jdoc:include type="modules" name="welcome" />
						</div>
					</div><!-- / .inner -->

				</div><!-- / .inner-wrap -->
			</div><!-- / #splash -->
		</div><!-- / #top -->

		<div id="wrap">
			<main id="content" class="<?php echo Request::getCmd('option', ''); ?>">
				<div class="inner<?php if ($this->countModules('left or right')) {
										echo ' withmenu';
									} ?>">
					<?php if ($this->countModules('left or right')) : ?>
						<section class="main section">
							<div class="section-inner">
							<?php endif; ?>

							<?php if ($this->countModules('left')) : ?>
								<aside class="aside">
									<jdoc:include type="modules" name="left" />
								</aside><!-- / .aside -->
							<?php endif; ?>
							<?php if ($this->countModules('left or right')) : ?>
								<div class="subject">
								<?php endif; ?>

								<!-- start landing slides -->
								<?php

								$filters = array();
								$filters['params'] = App::get('menu.params');
								$filters['start'] = 0;
								$filters['category_id'] = Request::getInt('id');
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
									<!---->
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


								<?php if ($this->countModules('left or right')) : ?>
								</div><!-- / .subject -->
							<?php endif; ?>
							<?php if ($this->countModules('right')) : ?>
								<aside class="aside">
									<jdoc:include type="modules" name="right" />
								</aside><!-- / .aside -->
							<?php endif; ?>

							<?php if ($this->countModules('left or right')) : ?>
							</div>
						</section><!-- / .main section -->
					<?php endif; ?>
				</div><!-- / .inner -->
			</main>

			<footer id="footer">
				<jdoc:include type="modules" name="footer" />
			</footer>
		</div><!-- / #wrap -->
	</div>
	<jdoc:include type="modules" name="endpage" />
</body>

</html>