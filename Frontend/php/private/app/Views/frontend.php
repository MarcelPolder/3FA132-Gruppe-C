<?php
namespace Webapp\Core;
?>
<!DOCTYPE html>
<html lang="<?=$this->data['lang']?>" dir="ltr" itemscope itemtype="http://schema.org/Organization" prefix="og:http://ogp.me/ns#">
<head>
	<title><?=$this->data['titel']?></title>
	<meta name="description" content="<?=$this->data['beschreibung']?>">
	<meta name="keywords" content="<?=$this->data['keywords']?>">

	<base href="<?=rURL()?>">

	<meta charset="utf-8">
	<meta name="copyright" content="<?=__('frontend.siteTitle')?> <?=date("Y")?>">
	<meta name="content-language" content="<?=$this->data['lang']?>">
	<meta name="robots" content="all">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<?php // Config ?>
<meta name="google-site-verification" content="">
	<link rel="shortcut icon" type="image/x-icon" href="/favicon/favicon.ico">
	<link rel="mask-icon" href="/favicon/safari-pinned-tab.svg" color="#0099aa">
	<link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="192x192" href="/favicon/android-chrome-192x192.png">
	<link rel="manifest" href="/favicon/site.webmanifest">
	<meta name="msapplication-config" content="/favicon/browserconfig.xml">
	<meta name="msapplication-TileColor" content="#212121">
	<meta name="theme-color" content="#212121">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="application-name" content="<?=__('frontend.siteTitle')?>">
	<meta name="apple-mobile-web-app-title" content="<?=__('frontend.siteTitle')?>">
	<meta name="theme-color" content="#0099aa">
	<meta name="msapplication-navbutton-color" content="#0099aa">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="msapplication-starturl" content="/">

	<?php // Allgemein ?>
<meta name="revisit-after" content="1 day">
	<meta property="og:locale" content="de_DE">
	<meta property='og:type' content='website'>
	<meta property='og:url' content='<?=URL.$_SERVER['REQUEST_URI']?>'>
	<meta property='og:title' content='<?=$this->data['titel']?>'>
	<meta property='og:description' content='<?=$this->data['beschreibung']?>'>
	<meta property="og:image" content="<?=URL.'/img/logo/logo.png'?>">

	<?php
	// CSS
	if(!empty($this->data['css'])) {
		ksort($this->data['css']);
		foreach($this->data['css'] as $css) {
			echo '<link rel="stylesheet" type="'.$css['type'].'" href="'.$css['href'].'">'."\n\t";
		}
		unset($this->data['css']);
	}
	?>

	<?php
	// JS
	if(!empty($this->data['js'])) {
		ksort($this->data['js']);
		foreach($this->data['js'] as $js) {
			$dataString = "";
			if(!empty($js['data'])) {
				foreach($js['data'] as $jsDataKey => $jsData) {
					if(!is_array($jsData)) $dataString .= ' data-'.$jsDataKey.'="'.$jsData.'"';
					else {
						$jsData = array_filter($jsData);
						if(!empty($jsData)) $dataString .= ' data-'.$jsDataKey.'="'.implode(',', $jsData).'"';
					}
				}
			}

			echo '<script'.($js['defer'] ? ' defer' : '').' type="'.$js['type'].'" src="'.$js['src'].'"'.(!empty($js['id']) ? ' id="'.$js['id'].'"' : '').$dataString.'></script>'."\n\t";
		}
		unset($this->data['js']);
	}
	?>

	<?php
	// Custom Head Elements
	if(!empty($this->data['headElements'])) {
		foreach($this->data['headElements'] as $headElement) {
			echo $headElement."\n\t";
		}
		unset($this->data['headElements']);
	}
	?>

	<script>if(top!=self) top.location = self.location;</script>

	<?php if(!empty($this->data['googleAnalytics']) && Config::get('rights.datenschutz.cookies')) : ?>
	<script>
	var googleAnalyticsId = '<?=$data['googleAnalytics']?>';
	(function googleAnalytics(){
		if(typeof window.mhCookie !== 'object') {
			setTimeout(googleAnalytics, 100);
		} else {
			if(window.mhCookie.check('googleAnalytics')) {
				// Global site tag (gtag.js) - Google Analytics
				(function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = 'https://www.googletagmanager.com/gtag/js?id='+googleAnalyticsId;
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag('js', new Date());
				gtag('set', 'anonymize_ip', true);
				gtag('config', googleAnalyticsId, { cookie_flags: 'secure;samesite=lax' });
			}
		}
	})();
	var disableStr = 'ga-disable-' + googleAnalyticsId;
	if (document.cookie.indexOf(disableStr + '=true') > -1) {
		window[disableStr] = true;
	}
	function gaOptout() {
		document.cookie = disableStr + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/';
		window[disableStr] = true;
		alert('Das Tracking durch Google Analytics wurde in Ihrem Browser für diese Website deaktiviert.');
	}
	</script>
	<?php endif; ?>
</head>
<body<?=(isset($this->data['darkmodeActive']) && $this->data['darkmodeActive'] ? " class='darkmode'" : "")?>>

	<div id="progressBar"></div>
	
	<header>
		<div class="inner">
			<div class="nav-header">
				<div class="text-left">
					<a href="#" class="nav-toggle"><i class="material-symbols-rounded">menu</i></a>
				</div>
				<div class="nav-site-title">
					<?=__($this->data['navActive'].".title", "Hausverwaltung")?>
				</div>
				<div class="text-right">
					<a href="/auth/logout"><i class="material-symbols-rounded">logout</i></a>
				</div>
			</div>
			<nav>
				<ul>
					<li class="nav-item">
						<a href="/"><i class="material-symbols-rounded s32 mr12 item-symbol">folder_managed</i><span class="item-text">Startseite</span></a>
					</li>
					<li class="nav-item">
						<a href="/customers"><i class="material-symbols-rounded s32 mr12 item-symbol">groups</i><span class="item-text">Kunden</span></a>
					</li>
					<li class="nav-item">
						<a href="/readings"><i class="material-symbols-rounded s32 mr12 item-symbol">library_books</i><span class="item-text">Zählerstände</span></a>
					</li>
					<li class="nav-item">
						<a href="/users"><i class="material-symbols-rounded s32 mr12 item-symbol">manage_accounts</i><span class="item-text">Benutzerverwaltung</span></a>
					</li>
				</ul>
			</nav>
		</div>
	</header>

	<main>
		<?php
		if(Session::hasFlash()) {
			echo Error::html(Session::flash(), Session::flashType());
		}
		?>
		<noscript>
			<div id="enableJavascript" class="error">
				<div class='inner'>
					<i class='material-symbols-rounded s48'>error</i>
					<?=__('javascript.text')?>
				</div>
			</div>
		</noscript>
		<?=$this->data['content']?>
		<footer>
			<section class="inner text-center">
				M. Polder, M. Kirchermeier, M. Krug, O. Fuchs &middot; &copy;&nbsp;<?=date('Y')?>
			</section>
		</footer>
	</main>

	<script type="application/ld+json">
	{
		"@context": "https://schema.org/",
		"@type": "WebSite",
		"name": "<?=__('frontend.siteTitle')?>",
		"url": "<?=URL?>"
	}
	</script>	
</body>
</html>