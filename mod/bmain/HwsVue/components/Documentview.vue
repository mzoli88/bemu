<template>
  <object
    v-if="src"
    ref="iframe"
    class="fit"
    :data="src"
    name="doc_iframe"
    frameborder="0"
  ></object>
  <div class="cflex fit" v-else>Nincs megjeleníthető dokumentum</div>
</template>

<script>
export default {
  watch: {
    file: function (f) {
      this.createSrc();
    },
  },
  mounted: function () {
    this.createSrc();
  },
  data: function () {
    return {
      src: null,
      blobUrl: null,
    };
  },
  props: {
    file: Object,
  },
  methods: {
    createSrc: function () {
      this.src = null;
      var type = this.canView();
      if (type) {
        // this.src = "data:" + type + ";base64," + this.file.file_content;
        this.src = this.blobUrl;
      }
    },
    canView: function () {
      if (empty(this.file)) return false;
      var type = this.file.type;
      if (empty(type)) {
        var ext = this.file.name.split(".").pop();
        switch (ext) {
          case "pptx":
          case "ppt":
            type = "application/mspowerpoint";
            break;
          case "csv":
          case "xls":
          case "xlsx":
            type = "application/msexcel";
            break;
          case "doc":
          case "docx":
            type = "application/msword";
            break;
        }
      }
      if (type == "application/pdf") {
        const blob = this.b64toBlob(this.file.file_content, type);
        const blobUrl = URL.createObjectURL(blob);
        // this.blobUrl = blobUrl1;

        this.blobUrl = blobUrl;
        return type;
      }

      //képeket mindig megjelenítünk
      if (/^image/.test(type)) {
        // this.blobUrl = "data:" + type + ";base64," + this.file.file_content;
        const blob = this.b64toBlob(this.file.file_content, type);
        const blobUrl = URL.createObjectURL(blob);
        // this.blobUrl = blobUrl1;

        this.blobUrl = blobUrl;
        return type;
      }

      if (!navigator || !navigator.mimeTypes) return false;
      //   dd(navigator.mimeTypes[type]);
      if (navigator.mimeTypes[type]) return type;

      return false;
    },
    b64toBlob: function (b64Data, contentType = "", sliceSize = 512) {
      const byteCharacters = atob(b64Data);
      const byteArrays = [];

      for (
        let offset = 0;
        offset < byteCharacters.length;
        offset += sliceSize
      ) {
        const slice = byteCharacters.slice(offset, offset + sliceSize);

        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
          byteNumbers[i] = slice.charCodeAt(i);
        }

        const byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
      }

      const blob = new Blob(byteArrays, { type: contentType });
      return blob;
    },
  },
};
</script>
