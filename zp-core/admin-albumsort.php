<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__) . '/admin-globals.php');

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}
admin_securityChecks($localrights, $return = currentRelativeURL());

if (isset($_GET['album'])) {
	$folder = sanitize($_GET['album']);
	$album = newAlbum($folder);
	if (!$album->isMyItem(ALBUM_RIGHTS)) {
		if (!zp_apply_filter('admin_managed_albums_access', false, $return)) {
			redirectURL(FULLWEBPATH . '/' . ZENFOLDER . '/admin.php');
		}
	}
	if (isset($_GET['saved'])) {
		XSRFdefender('save_sort');
		if (isset($_POST['ids'])) { //	process bulk actions, not individual image actions.
			$action = processImageBulkActions($album);
			if (!empty($action)) {
				$_GET['bulkmessage'] = $action;
			}
		} else {
			$orderArray = explode('&', str_replace('id[]=', '', $_POST['sortableList']));
			if (is_array($orderArray) && !empty($orderArray)) {
				foreach ($orderArray as $key => $id) {
					$sql = 'UPDATE ' . prefix('images') . ' SET `sort_order`=' . db_quote(sprintf('%03u', $key)) . ' WHERE `id`=' . sanitize_numeric($id);
					query($sql);
				}
				$album->setSortType("manual");
				$album->setSortDirection(false, 'image');
				$album->setLastChangeUser($_zp_current_admin_obj->getUser());
				$album->save();
				$_GET['saved'] = 1;
			}
		}
		if(!isset($_POST['checkForPostTruncation'])) {
			$_GET['post_error'] = 1;
		}
	} 
	
}


// Print the admin header
setAlbumSubtabs($album);
printAdminHeader('edit', 'sort');

?>
<script type="text/javascript">
	//<!-- <![CDATA[
	$(function() {
		$('#images').sortable();
	});
	// ]]> -->
</script>
<?php
echo "\n</head>";
?>


<body>

	<?php
	$checkarray_images = array(
					gettext('*Bulk actions*')			 => 'noaction',
					gettext('Delete')							 => 'deleteall',
					gettext('Set to published')		 => 'showall',
					gettext('Set to unpublished')	 => 'hideall',
					gettext('Add tags')						 => 'addtags',
					gettext('Clear tags')					 => 'cleartags',
					gettext('Disable comments')		 => 'commentsoff',
					gettext('Enable comments')		 => 'commentson',
					gettext('Change owner')				 => 'changeowner'
	);
	if (extensionEnabled('hitcounter')) {
		$checkarray_images[gettext('Reset hitcounter')] = 'resethitcounter';
	}
	$checkarray_images = zp_apply_filter('bulk_image_actions', $checkarray_images);

// Create our album
	if (!isset($_GET['album'])) {
		zp_error(gettext("No album provided to sort."));
	} else {
		// Layout the page
		printLogoAndLinks();
		?>

		<div id="main">
			<?php printTabs(); ?>
			<div id="content">
				<?php
				zp_apply_filter('admin_note', 'albums', 'sort');
				if ($album->getParent()) {
					$link = getAlbumBreadcrumbAdmin($album);
				} else {
					$link = '';
				}
				$alb = removeParentAlbumNames($album);
				?>
				<h1><?php printf(gettext('Edit Album: <em>%1$s%2$s</em>'), $link, $alb); ?></h1>
				<?php
				$images = $album->getImages();
				$subtab = printSubtabs();

				$parent = dirname($album->name);
				if ($parent == '/' || $parent == '.' || empty($parent)) {
					$parent = '';
				} else {
					$parent = '&amp;album=' . $parent . '&amp;tab=subalbuminfo';
				}
				?>

				<div class="tabbox">
					<?php
					if (isset($_GET['saved'])) {
						if (sanitize_numeric($_GET['saved'])) {
							consolidatedEditMessages($subtab);
						} else {
							if (isset($_GET['bulkmessage'])) {
								consolidatedEditMessages($subtab);
							} 
						}
					} 
					?>
					<form class="dirty-check" action="?page=edit&amp;album=<?php echo $album->getFileName(); ?>&amp;saved&amp;tab=sort" method="post" name="sortableListForm" id="sortableListForm" autocomplete="off">
						<?php XSRFToken('save_sort'); ?>
						<?php printBulkActions($checkarray_images, true); ?>
						<script type="text/javascript">
							// <!-- <![CDATA[
							function postSort(form) {
								$('#sortableList').val($('#images').sortable('serialize'));
								form.submit();
							}
							// ]]> -->
						</script>

						<p class="buttons">
							<a href="<?php echo WEBPATH . '/' . ZENFOLDER . '/admin-edit.php?page=edit' . $parent; ?>"><img	src="images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
							<button type="submit" onclick="postSort(this.form);" >
								<img	src="images/pass.png" alt="" />
								<strong><?php echo gettext("Apply"); ?></strong>
							</button>
							<a href="<?php echo WEBPATH . "/index.php?album=" . html_encode(pathurlencode($album->getFileName())); ?>">
								<img src="images/view.png" alt="" />
								<strong><?php echo gettext('View Album'); ?></strong>
							</a>
						</p>
						<br class="clearall" /><br />
						<p><?php echo gettext("Set the image order by dragging them to the positions you desire."); ?></p>

						<ul id="images">
							<?php
							$images = $album->getImages();
							foreach ($images as $imagename) {
								$image = newImage($album, $imagename);
								?>
								<li id="id_<?php echo $image->getID(); ?>">
									<div  class="images_publishstatus">
										<?php 
										if($image->getShow()) { 
											$publishstatus_text = gettext('Published');
											$publishstatus_icon = '/images/pass.png';
										} else {
											$publishstatus_text = gettext('Unpublished');
											$publishstatus_icon = '/images/action.png';
										}
										?>
										<img src="<?php echo WEBPATH . '/' . ZENFOLDER . $publishstatus_icon; ?>" alt="<?php echo $publishstatus_text; ?>">
									</div>
									<img class="imagethumb"
											 src="<?php echo getAdminThumb($image, 'large'); ?>"
											 alt="<?php echo html_encode($image->getTitle()); ?>"
											 title="<?php echo html_encode($image->getTitle()) . ' (' . html_encode($image->getFileName()) . ')'; ?>"
											 width="80" height="80"  />
									<p>
										<input type="checkbox" name="ids[]" value="<?php echo $image->filename; ?>">
										<a href="<?php echo WEBPATH . "/" . ZENFOLDER; ?>/admin-edit.php?page=edit&amp;album=<?php echo pathurlencode($album->name); ?>&amp;image=<?php echo urlencode($image->filename); ?>&amp;tab=imageinfo#IT" title="<?php echo gettext('edit'); ?>"><img src="<?php echo WEBPATH . '/' . ZENFOLDER; ?>/images/pencil.png" alt=""></a>
										<?php
										if (isImagePhoto($image)) {
											?>
											<a href="<?php echo html_encode(pathurlencode($image->getFullImageURL())); ?>" class="colorbox" title="zoom"><img src="<?php echo WEBPATH . '/' . ZENFOLDER; ?>/images/magnify.png" alt=""></a>
											<?php
										}
										?>
									</p>
									<?php
								}
								?>
							</li>
						</ul>
						<br class="clearall" />

						<div>
							<input type="hidden" id="sortableList" name="sortableList" value="" />
							<p class="buttons">
								<a href="<?php echo WEBPATH . '/' . ZENFOLDER . '/admin-edit.php?page=edit' . $parent; ?>">
									<img	src="images/arrow_left_blue_round.png" alt="" />
									<strong><?php echo gettext("Back"); ?></strong>
								</a>
								<button type="submit" onclick="postSort(this.form);" >
									<img	src="images/pass.png" alt="" />
									<strong><?php echo gettext("Apply"); ?></strong>
								</button>
								<a href="<?php echo WEBPATH . "/index.php?album=" . html_encode(pathurlencode($album->getFileName())); ?>">
									<img src="images/view.png" alt="" />
									<strong><?php echo gettext('View Album'); ?></strong>
								</a>
							</p>
						</div>
						<input type="hidden" name="checkForPostTruncation" value="1" />
					</form>
					<br class="clearall" />

				</div>

			</div>

		</div>

		<?php
		printAdminFooter();
	}
	?>

</body>

<?php
echo "\n</html>";
?>
