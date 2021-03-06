/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	OCA.SystemTags = _.extend({}, OCA.SystemTags);
	if (!OCA.SystemTags) {
		/**
		 * @namespace
		 */
		OCA.SystemTags = {};
	}

	/**
	 * @namespace
	 */
	OCA.SystemTags.FilesPlugin = {
		allowedLists: [
			'files',
			'favorites',
			'systemtagsfilter'
		],

		attach: function(fileList) {
			if (this.allowedLists.indexOf(fileList.id) < 0) {
				return;
			}

			fileList.registerDetailView(new OCA.SystemTags.SystemTagsInfoView());
		}
	};

})();

OC.Plugins.register('OCA.Files.FileList', OCA.SystemTags.FilesPlugin);

