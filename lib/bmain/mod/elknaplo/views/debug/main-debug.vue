<template>
  <Grid v-if="fastFilter" :fastFilter="fastFilter" store="debug" :setRowClass="setRowClass" :cols="{
    datetime: {
      title: 'Dátum',
      sortable: false,
      type: 'date',
      search: 'date',
    },
    all: {
      sortable: false,
      list: true,
      title: 'Alap adatok',
      search: false,
    },
    displayContent: {
      search: false,
      sortable: false,
      title: 'Szöveg',
      type: 'string',
      focusable: true,
    },
    category: {
      list: false,
      sortable: false,
      title: 'Kategória',
      search: 'radiogroup',
      boxes: [
        { value: 'error', name: 'error' },
        { value: 'audit', name: 'audit' },
        { value: 'system', name: 'system' },
        { value: 'security', name: 'security' },
        { value: 'debug', name: 'debug' },
      ],
    },
    level: {
      list: false,
      sortable: false,
      title: 'Naplózási szint',
      search: 'radiogroup',
      boxes: [
        { value: 'error', name: 'Error' },
        { value: 'info', name: 'Info' },
        { value: 'debug', name: 'Debug' },
      ],
    },
    entity_name: {
      list: false,
      sortable: false,
      title: 'Entitás',
      type: 'string',
    },
    request_id: {
      list: false,
      sortable: false,
      title: 'Kérés azonosító',
      type: 'string',
    },
    uuid: {
      list: false,
      sortable: false,
      title: 'Belépés azonosító',
      type: 'string',
    },
    method: {
      list: false,
      sortable: false,
      title: 'Method',
      search: 'combo',
      boxes: [
        { value: 'GET', name: 'GET' },
        { value: 'POST', name: 'POST' },
        { value: 'PUT', name: 'PUT' },
        { value: 'DELETE', name: 'DELETE' },
        { value: 'CMD', name: 'CMD' },
      ],
    },
    user_id: {
      list: false,
      sortable: false,
      title: 'Felhasználó id',
      type: 'number',
    },
    user_name: {
      list: false,
      sortable: false,
      title: 'Felhasználó neve',
      type: 'string',
    },
    ip: {
      list: false,
      sortable: false,
      title: 'IP',
      type: 'string',
    },
    uri: {
      list: false,
      sortable: false,
      title: 'URI',
      type: 'string',
    },
    message: {
      list: false,
      sortable: false,
      title: 'Szöveg',
      type: 'string',
      focusable: true,
    },
  }">
    <template v-slot:all="rowData">
      <div class="AllData">
        <div>
          <span>Kategória:</span><span>{{ rowData.category }}</span>
        </div>
        <div v-if="rowData.level">
          <span>Naplózási szint:</span><span>{{ rowData.level }}</span>
        </div>
        <div v-if="rowData.event">
          <span>Esemény:</span><span>{{ rowData.event }}</span>
        </div>
        <div v-if="rowData.app_event">
          <span>Esemény:</span><span>{{ rowData.app_event }}</span>
        </div>
        <div>
          <span>Entitás:</span><span>{{ rowData.entity_name }}</span>
        </div>
        <div>
          <span>Kérés azon:</span><span>{{ rowData.request_id }}</span>
        </div>
        <div>
          <span>Belépés azon:</span><span>{{ rowData.uuid }}</span>
        </div>
        <div>
          <span>Felhasználó:</span>
          <span>
            {{ rowData.user_name }}
            <div v-if="rowData.user_id && rowData.user_id != '-'">
              {{ rowData.user_id + ", " + rowData.user_login }}
            </div>
          </span>
        </div>
        <div v-if="rowData.ip">
          <span>IP:</span><span>{{ rowData.ip }}</span>
        </div>
        <div v-if="rowData.microtime">
          <span>Microtime:</span><span>{{ rowData.microtime }}</span>
        </div>
        <div>
          <span>Szofver:</span><span>{{ rowData.software_id }}</span>
        </div>
        <div v-if="rowData.past_time">
          <span>Eltelt idő (s):</span><span>{{ rowData.past_time }}</span>
        </div>
        <div v-if="rowData.code">
          <span>Code:</span><span>{{ rowData.code }}</span>
        </div>
        <div v-if="rowData.error">
          <span>Error:</span><span>{{ rowData.error }}</span>
        </div>
      </div>
    </template>
    <template v-slot:displayContent="rowData">
      <div class="uriContent">
        <span>{{ rowData.method }}:</span> <span>{{ rowData.uri }}</span>
      </div>
      <div class="NaploContent" tabindex="0" @focus="onCtFocus" v-html="rowData.message"></div>
    </template>
  </Grid>
</template>

<script>
export default {
  created: function () {
    getStore("mods").load((s, d) => (this.fastFilter = d));

    var st = getStore("debug");
    st.onLoad(function (s, d) {
      if (s && d.total) {
        st.fixQuery("total", d.total);
      }
    }, this);

    document.addEventListener("keydown", function (e) {
      // CTRL + a működjön a tartalomra
      if (e.key == "a" && e.ctrlKey) {
        if (document.activeElement) {
          e.preventDefault();
          window.getSelection().removeAllRanges();
          var range = document.createRange();
          range.selectNode(document.activeElement);
          window.getSelection().addRange(range);
        }
      }
    });
  },
  data: function () {
    return {
      fastFilter: null,
    };
  },

  methods: {
    setRowClass: function (rowData) {
      switch (rowData.level) {
        case "error":
          return "RED";
        case "info":
          return "GREEN";
      }
    },
    onCtFocus: function (e) {
      var active = e.target.parentNode;
      if (active) {
        active.scrollIntoView({
          behavior: "smooth",
          block: "center",
          inline: "center",
        });
      }
    },
  },
};
</script>

<style>
.GridRow>td.displayContent>.GridCelContent {
  max-width: unset;
  display: block;
  text-overflow: initial;
  width: auto;
  height: auto;
}

.NaploContent {
  cursor: pointer;
  height: 200px;
  min-width: 150px;
  overflow: hidden;
  margin: 5px;
  border: 2px solid #eef0f6;
  border-radius: 4px;
  background-color: #fff;
  padding: 4px;

  display: block;
  unicode-bidi: embed;
  font-family: monospace;

  white-space: -moz-pre-wrap;
  white-space: -pre-wrap;
  white-space: -o-pre-wrap;
  white-space: pre-wrap;

  overflow-wrap: break-word;
  word-wrap: break-word;

  -ms-word-break: break-all;
  word-break: break-word;

  -ms-hyphens: auto;
  -moz-hyphens: auto;
  -webkit-hyphens: auto;
  hyphens: auto;
}

.GridRow>td.displayContent>.GridCelContent .NaploContent:focus {
  border: 2px solid rgba(56, 102, 193, 0.6);
  overflow: auto;
  width: auto;
  height: auto;
  cursor: initial;
  min-height: 200px;
  max-height: 800px;
}

.AllData {
  display: table;
  white-space: normal;
}

.AllData>div {
  display: table-row;
}

.AllData>div>span {
  display: table-cell;
  padding: 1px 5px;
}

.AllData>div>span:first-child {
  text-wrap: nowrap;
}

.AllData>div>span:last-child {
  font-weight: bold;
}

.uriContent {
  margin: 2px 6px;
}
</style>
