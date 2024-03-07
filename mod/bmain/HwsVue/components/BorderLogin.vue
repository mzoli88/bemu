<template>
  <div id="app" class="hflex fit" azon="App">
    <Popup></Popup>
    <iframe :src="loginUrl" class="fit" @load="onLoad" ref="iframe"></iframe>
  </div>
</template>

<script>
import Popup from "./Popup.vue";
export default {
  data: function () {
    return {
      loginUrl: location.href.split("/mod/").shift() + "/belep.php",
    };
  },
  mounted: function () {
    msg("Nincs belépve!", "error");
  },
  methods: {
    onLoad: function (e) {
      if (
        !this.$refs.iframe.contentWindow.location.href.includes("/belep.php")
      ) {
        window.open(location.href, "_blank").focus();
        window.location.replace(this.$refs.iframe.contentWindow.location.href);
      }
    },
  },
  components: {
    Popup,
  },
};
</script>

<style>
iframe {
  border: none;
}
</style>
