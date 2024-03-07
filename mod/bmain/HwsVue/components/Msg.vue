<template>
  <transition name="move-in">
    <div
      id="msg"
      v-if="txt"
      :class="cls"
      v-html="txt + ' (' + time + ')'"
    ></div>
  </transition>
</template>

<script>
export default {
  created: function () {
    global.tMsg = this;
  },
  data: function () {
    return {
      time: 0,
      txt: false,
      counting: false,
      cls: null,
    };
  },
  methods: {
    timedMsg: function (txt, type, sec) {
      var me = this;
      if (me.txt) {
        me.txt = false;
        setTimeout(function () {
          me.timedMsg(txt, type, sec);
        }, 500);
      } else {
        me.cls = type || "info";
        me.txt = txt;
        me.time = sec || 5;
        if (me.counting === false) me.doCount();
      }
    },
    doCount: function () {
      var me = this;
      me.counting = true;
      if (me.time < 1) {
        me.counting = false;
        me.txt = false;
      } else {
        setTimeout(function () {
          me.time--;
          me.doCount();
        }, 1000);
      }
    },
    s: function (txt, sec) {
      this.timedMsg(txt, "success", sec);
    },
    i: function (txt, sec) {
      this.timedMsg(txt, "info", sec);
    },
    e: function (txt, sec) {
      this.timedMsg(txt, "error", sec);
    },
  },
};
</script>

<style scoped>
#msg {
  position: absolute;
  z-index: 10000;
  top: 5px;
  padding: 10px;
  border: 2px solid transparent;
  border-radius: 4px;
  background-color: #fff;
  font-weight: bold;
  min-width: 300px;
  left: 50%;
  transform: translate(-50%, 0);
  text-align: center;
}

#msg.success {
  color: green;
  border-color: green;
}

#msg.error {
  color: #c41717;
  border-color: #c41717;
}

#msg.info {
  color: var(--higlight-text-color);
  border-color: var(--higlight-text-color);
}

.move-in-enter-active {
  animation: move-in 0.5s;
}

.move-in-leave-active {
  animation: move-in 0.5s reverse;
}

@keyframes move-in {
  0% {
    top: -100px;
  }

  100% {
    top: 5px;
  }
}
</style>