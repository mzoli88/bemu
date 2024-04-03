<template>
  <form :action="url" class="padding" method="post" enctype="multipart/form-data">
    <div class="fieldbody FileField fit vflex pointer" @click.self="onClick">
      <input type="file" class="Textfield fakeFileimput fit nopointerevent pointer fieldBorder required" ref="filefield"
        accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
      <div class="fieldButton cflex nopointerevent pointer">
        <span class="icon upload"></span>
      </div>
    </div>
  </form>
  <div class="toolbar padding">
    <Button @click="onSave"> Importálás </Button>
    <Button @click="onBack" title="Mégsem" />
  </div>
</template>


<script>
export default {
  props: {
    store: String,
  },
  data: function () {
    return {
      value: null,
      url: getStore(this.store).$url + "/import",
    };
  },
  methods: {
    onClick: function (e) {
      if (this.disabled) return false;
      this.$refs.filefield.click();
    },
    onSave: function () {
      let file = this.$refs.filefield;
      this.FileUpload(file, this.url);
    },

    FileUpload: function (file_field, url) {
      const file = file_field.files[0];
      var me = this;

      var formData = new FormData();
      formData.append("import_file", file);
      var xhttp = new XMLHttpRequest();
      xhttp.open(
        "POST",
        url,
        true
      );

      xhttp.timeout = 9999999999999999;
      xhttp.responseType = "blob";

      if (Auth.token) {
        xhttp.setRequestHeader("Authorization", "Bearer " + Auth.token);
      }
      if (global.getActiveEntity) {
        xhttp.setRequestHeader("Active-Entity", getActiveEntity());
      }

      maskOn();

      xhttp.onreadystatechange = function () {
        if (this.readyState != 4) return;

        this.response.text().then((d) => {
          maskOff();
          if (/^\{.*\}$/.test(d)) {
            d = JSON.parse(d);
            if (d.success == false || this.status != 200) {
              msg("Sikertelen importálás!", "error", null, d.message);
            } else {
              if (d.hasOwnProperty("Queque")) doQueque(d);
              me.onBack();
            }
          } else {
            if (this.getResponseHeader("Content-Disposition")) {
              var contentDispo = this.getResponseHeader("Content-Disposition");
              if (contentDispo) {
                var a = document.createElement("a");
                a.href = window.URL.createObjectURL(this.response);
                a.download = contentDispo
                  .match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)[1]
                  .replaceAll('"', "");
                a.dispatchEvent(new MouseEvent("click"));
              }
            } else {
              if (this.status == 200) {
                msg("Sikeres importálás!", "info");
              } else {
                msg("Sikertelen importálás!", "error");
              }
            }
          }
        });
      };
      xhttp.send(formData);
    },
    onBack: function () {
      this.up("Grid").toList();
    },
  },
};
</script>