<div id="stack-add" ng-if="boardservice.canEdit() && checkCanEdit()">
    <form class="ng-pristine ng-valid" ng-submit="createStack()">
        <input type="text" class="no-close" placeholder="<?php p($l->t('Add a new stack')); ?>"
            ng-focus="status.addStack=true"
            ng-blur="status.addStack=false"
            ng-model="newStack.title" required
            maxlength="100" />
        <button class="button-inline icon icon-add" ng-style="{'opacity':'{{status.addStack ? 1: 0.5}}'}" type="submit" title="<?php p($l->t('Submit')); ?>">
        	<span class="hidden-visually"><?php p($l->t('Submit')); ?></span>
        </button>
    </form>
</div>

<button ng-if="params.filter!='archive'" ng-click="switchFilter('archive')" style="opacity:0.5;" title="<?php p($l->t('Show archived cards')); ?>">
    <i class="icon icon-archive"></i>
    <span class="hidden-visually"><?php p($l->t('Show archived cards')); ?></span>
</button>
<button ng-if="params.filter=='archive'" ng-click="switchFilter('')" title="<?php p($l->t('Hide archived cards')); ?>">
    <i class="icon icon-archive"></i>
    <span class="hidden-visually"><?php p($l->t('Hide archived cards')); ?></span>
</button>
<button ui-sref="board.detail({ id: id, tab: 0})"  title="<?php p($l->t('Board details')); ?>">
    <i class="icon icon-settings"></i>
    <span class="hidden-visually"><?php p($l->t('Board details')); ?></span>
</button>
