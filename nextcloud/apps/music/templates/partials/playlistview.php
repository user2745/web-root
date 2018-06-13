<div class="playlist-area" ng-show="!loading && !loadingCollection">
	<h1>
		<span ng-click="playAll()">
			<span ng-if="playlist">{{ playlist.name }}</span>
			<span ng-if="currentView == '#/alltracks'" translate>All tracks</span>
			<img class="play svg" alt="{{ 'Play' | translate }}" src="<?php p(OCP\Template::image_path('music', 'play-big.svg')) ?>"/>
		</span>
	</h1>
	<ul class="track-list">
		<li bindonce ng-repeat="entry in tracks | limitTo: incrementalLoadLimit"
			ng-init="song = entry.track"
			bo-id="'track-' + song.id"
			ui-on-drop="reorderDrop($data, $index)"
			ui-on-drag-enter="updateHoverStyle($index)"
			drop-validate="allowDrop($data, $index)"
			drag-hover-class="drag-hover">
			<div>
				<div ng-click="playTrack($index)" ui-draggable="true" drag="getDraggable($index)"
					ng-class="{current: getCurrentTrackIndex() === $index, playing: playing}"
				>
					<div class="play-pause" />
					<span class="muted">{{ $index + 1 }}.</span>
					<div bo-text="song.artistName + ' - ' + song.title"></div>
				</div>
				<button class="svg action icon-close" ng-click="removeTrack($index)" bo-if="playlist"
					bo-alt="'Remove' | translate" bo-title="'Remove track from playlist' | translate"></button>
			</div>
		</li>
	</ul>

	<div id="emptycontent" ng-show="playlist && playlist.tracks.length == 0 && !scanning && !toScan && !noMusicAvailable">
		<div class="icon-audio svg"></div>
		<h2 translate>No tracks</h2>
		<p translate>Add tracks with drag and drop from Albums or other playlists</p>
	</div>

</div>
