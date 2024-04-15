<?php
namespace Webapp\Lib;

use PHPMailer\PHPMailer\SMTP;
use Webapp\Core\Config;

class Mailer extends \PHPMailer\PHPMailer\PHPMailer {

	function __construct(bool $exceptions = null) {
		parent::__construct($exceptions);
	}

	public function sendMail($options = ['to' => '', 'toName' => '', 'from' => '', 'fromName' => '', 'subject' => '', 'body' => '', 'bodyAlt' => '', 'isHtml' => null, 'debug' => null]) {
		$settings = array_replace(
			[
				'to' => '',
				'toName' => '',
				'from' => Config::get('mail.fromAddress'),
				'fromName' => Config::get('mail.fromName'),
				'replyTo' => '',
				'replyToName' => '',
				'subject' => '',
				'body' => '',
				'bodyAlt' => '',
				'isHtml' => true,
				'debug' => false
			],
			array_filter($options)
		);
		if(!empty($settings['to']) && !empty($settings['subject']) && (!empty($settings['body']) || !empty($settings['bodyAlt']))) {
			$this->setLanguage('de');
			
			// Server settings
			if($settings['debug']!==false) $this->SMTPDebug = ($settings['debug']===true ? SMTP::DEBUG_SERVER : $settings['debug']);
			$this->isSMTP();
			$this->Host       = Config::get('mail.smtp.host');
			$this->SMTPAuth   = Config::get('mail.smtp.auth');
			$this->Username   = Config::get('mail.smtp.user');
			$this->Password   = Config::get('mail.smtp.pass');
			$this->SMTPSecure = Config::get('mail.smtp.secure');
			$this->Port       = Config::get('mail.smtp.port');
		
			//Recipients
			$this->setFrom($settings['from'], $settings['fromName']);
			$this->addAddress($settings['to'], $settings['toName']);
			if(!empty($settings['replyTo'])) {
				$this->addReplyTo($settings['replyTo'], $settings['replyToName']);
			}
		
			// Content
			$this->isHTML($settings['isHtml']);
			$this->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
			$this->Subject = $settings['subject'];
			$this->Body    = $this->getTemplate($settings['subject'], $settings['body']);
			if(!empty($settings['bodyAlt'])) $this->AltBody = $settings['bodyAlt'];
		
			return $this->send();
		}
		return false;
	}

	public function getTemplate($subject = '', $content = '') {
		return '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
		<head>
		<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
		<meta content="width=device-width" name="viewport"/>
		<!--[if !mso]><!-->
		<meta content="IE=edge" http-equiv="X-UA-Compatible"/>
		<!--<![endif]-->
		<title>'.$subject.'</title>
		<!--[if !mso]><!-->
		<!--<![endif]-->
		<style type="text/css">
		body {
			margin: 0;
			padding: 0;
			color: #FAFAFA !important;
			font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, sans-serif;
		}
		table,
		td,
		tr {
			vertical-align: top;
			border-collapse: collapse;
		}
		* {
			line-height: inherit;
		}
		h1, h2, h3, h4, h5, h6 {
			margin-top: 0;
			text-align: center;
			font-weight: 400;
			font-family: \''.Config::get('mail.fontFamily.headlines').'\', Arial, Tahoma, Verdana, sans-serif;
		}
		a {
			color: '.Config::get('mail.linkColor').' !important;
			text-decoration: none !important;
			font-size: 14px;
			line-height: 1.25rem;
		}
		a[x-apple-data-detectors=true] {
			color: inherit !important;
			text-decoration: none !important;
		}
		.btn {
			display: inline-block;
			margin: auto;
			padding: 12px 24px;
			vertical-align: middle;
			text-align: center;
			text-decoration: none;
			letter-spacing: 0.5px;
			border: none;
			border-radius: 4px;
			color: #fff !important;
			cursor: pointer;
			background-color: '.Config::get('mail.linkColor').';
			-webkit-appearance: none;
			-webkit-tap-highlight-color: transparent;
			-webkit-transition: background-color 0.2s ease-out;
			transition: background-color 0.2s ease-out;
		}
		.btn:hover {
			background-color: #007788;
		}
		.text-center {
			text-align: center;
		}
		</style>
		<style id="media-query" type="text/css">
		@media (max-width: 620px) {
			.block-grid,
			.col {
				min-width: 320px !important;
				max-width: 100% !important;
				display: block !important;
			}
			.block-grid {
				width: 100% !important;
			}
			.col {
				width: 100% !important;
			}
			.col>div {
				margin: 0 auto;
			}
			img.fullwidth,
			img.fullwidthOnMobile {
				max-width: 100% !important;
			}
		}
		</style>
		'.(!in_array(strtolower(Config::get('mail.fontFamily')), ["arial","tahoma","verdana","sans-serif"]) ? '<link href="'.Config::get('mail.url').'/font/'.strtolower(Config::get('mail.fontFamily')).'" rel="stylesheet">' :'').'
		'.(strtolower(Config::get('mail.fontFamily.headlines'))!=strtolower(Config::get('mail.fontFamily')) ? '<link href="'.Config::get('mail.url').'/font/'.strtolower(Config::get('mail.fontFamily.headlines')).'" rel="stylesheet">' :'').'
		</head>
		<body class="clean-body" style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; background-color: #212121;">
			<!--[if IE]><div class="ie-browser"><![endif]-->
			<table bgcolor="#212121" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="table-layout: fixed; vertical-align: top; min-width: 320px; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #212121; width: 100%;" valign="top" width="100%">
				<tbody>
					<tr style="vertical-align: top;" valign="top">
						<td style="word-break: break-word; vertical-align: top;" valign="top">
							<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color:#212121"><![endif]-->
							'.(!empty(Config::get('mail.logo')) ? '
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: transparent;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:transparent;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:24px; padding-bottom:0px;"><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:24px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
													<!--<![endif]-->
													<div align="center" class="img-container center fixedwidth fullwidthOnMobile" style="padding-right: 0px;padding-left: 0px;">
														<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 0px;padding-left: 0px;" align="center"><![endif]--><a href="'.Config::get('mail.url').'" style="outline:none" tabindex="-1" target="_blank"> <img align="center" alt="Logo" border="0" class="center fixedwidth fullwidthOnMobile" src="'.Config::get('mail.url').Config::get('mail.logo').'" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; width: 100%; max-width: 300px; display: block;" title="Logo" width="300"/></a>
														<!--[if mso]></td></tr></table><![endif]-->
													</div>
													<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							' : '').'
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: transparent;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:transparent;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px;"><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
													<!--<![endif]-->
													<div style="height: 24px"></div>
													<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: #424242;  border-radius: 4px;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:#424242; border-radius: 4px;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:#424242;border-radius: 4px;"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:#424242;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; border-radius: 4px;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 24px; padding-left: 24px; padding-top:24px; padding-bottom:24px;"><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:24px; padding-bottom:24px; padding-right: 24px; padding-left: 24px;">
												<!--<![endif]-->
													'.$content.'
												<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: transparent;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:transparent;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:4px; padding-bottom:4px;"><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:4px; padding-bottom:4px; padding-right: 0px; padding-left: 0px;">
												<!--<![endif]-->
													<div style="height: 24px"></div>
												<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: transparent;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:transparent;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent;">
												<!--<![endif]-->
													<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 12px; padding-left: 12px; padding-top: 12px; padding-bottom: 12px; font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, sans-serif"><![endif]-->
													<div style="color:#212121;font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, Segoe, sans-serif;line-height:1.25rem;padding-top:12px;padding-right:12px;padding-bottom:12px;padding-left:12px;">
														<div style="line-height: 1.25rem; font-size: 12px; color: #212121; font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, Segoe, sans-serif; mso-line-height-alt: 14px;">
															<p style="font-size: 14px; line-height: 1.25rem; word-break: break-word; text-align: center; mso-line-height-alt: 17px; margin: 0;"><a href="'.Config::get('mail.url').'" rel="noopener" style="text-decoration: none; color: '.Config::get('mail.linkColor').';" target="_blank">zur Webseite</a></p>
														</div>
													</div>
													<!--[if mso]></td></tr></table><![endif]-->
												<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							<div style="background-color:transparent;overflow:hidden">
								<div class="block-grid" style="min-width: 320px; max-width: 600px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin: 0 auto; width: 100%; background-color: transparent;">
									<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
										<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align=""><table cellpadding="0" cellspacing="0" border="0" style="width:600px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
										<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color:transparent;width:600px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 12px; padding-left: 12px; padding-top:0px; padding-bottom:24px;"><![endif]-->
										<div class="col num12" style="min-width: 320px; max-width: 600px; display: table-cell; vertical-align: top; width: 600px;">
											<div style="width:100% !important;">
												<!--[if (!mso)&(!IE)]><!-->
												<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top: 0px; padding-bottom: 24px;">
												<!--<![endif]-->
													<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, sans-serif"><![endif]-->
													<div style="color:#212121;font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, Segoe, sans-serif;line-height:1.25rem;">
														<div style="line-height: 1.25rem; font-size: 12px; color: #212121; font-family: \''.Config::get('mail.fontFamily').'\', Arial, Tahoma, Verdana, Segoe, sans-serif; mso-line-height-alt: 14px;">
															<p style="font-size: 14px; line-height: 1.25rem; word-break: break-word; text-align: center; mso-line-height-alt: 17px; margin: 0;"><span style="color: #BDBDBD;">Falls du diese E-Mail nicht zuordnen kannst, lösche sie einfach.</span></p>
														</div>
													</div>
													<!--[if mso]></td></tr></table><![endif]-->
												<!--[if (!mso)&(!IE)]><!-->
												</div>
												<!--<![endif]-->
											</div>
										</div>
										<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
										<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
									</div>
								</div>
							</div>
							<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
						</td>
					</tr>
				</tbody>
			</table>
			<!--[if (IE)]></div><![endif]-->
		</body>
		</html>
		';
	}

}