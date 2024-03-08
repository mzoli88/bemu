<template>
  <Transition name="maskfade">
    <div v-if="show" class="cflex loadmask fit noselect">
      <div class="spin">
        <Icon>f51f</Icon>
      </div>
    </div>
  </Transition>
</template>
<script>
export default {
  props: {
    show: Boolean,
  },
};
</script>

<style>
.spin {
  animation: spin 1.2s infinite;
}

.loadmask .icon {
  color: var(--higlight-text-color);
  font-size: 100px;
}

.loadmask {
  background-color: #f1f1f1;
}

.maskfade-leave-active,
.maskfade-enter-active {
  transition: opacity 0.4s ease;
}

.maskfade-enter-from,
.maskfade-leave-to {
  opacity: 0;
}

.maskfade2-leave-active,
.maskfade2-enter-active {
  transition: opacity 0.4s ease;
}

.maskfade2-leave-to {
  opacity: 0;
}

@keyframes spin {
  0% {
    transform: rotate(0);
    animation-timing-function: cubic-bezier(0.55, 0.055, 0.675, 0.19);
  }

  50% {
    transform: rotate(900deg);
    animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
  }

  100% {
    transform: rotate(1800deg);
  }
}
</style>