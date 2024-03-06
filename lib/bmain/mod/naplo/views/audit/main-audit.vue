<template>
  <Grid
    v-if="fastFilter"
    :fastFilter="fastFilter"
    store="audit"
    :setRowClass="setRowClass"
    :cols="{
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
      event: {
        list: false,
        sortable: false,
        title: 'Esemény',
        type: 'string',
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
        search: 'checkboxgroup',
        boxes: [
          { value: 'GET', name: 'GET' },
          { value: 'POST', name: 'POST' },
          { value: 'PUT', name: 'PUT' },
          { value: 'DELETE', name: 'DELETE' },
          { value: 'CMD', name: 'CMD' },
        ],
      },
      user_name: {
        list: false,
        sortable: false,
        title: 'Felhasználó neve',
        type: 'string',
      },
      uri: {
        list: false,
        sortable: false,
        title: 'URI',
        type: 'string',
      },
      code: {
        title: 'Code',
        list: false,
        sortable: false,
        type: 'string',
      },
      error: {
        list: false,
        sortable: false,
        title: 'Error',
        type: 'string',
      },
      message: {
        list: false,
        sortable: false,
        title: 'Szöveg',
        type: 'string',
        focusable: true,
      },
    }"
  >
    <template v-slot:all="rowData">
      <div class="AllData">
        <div>
          <span>Esemény:</span><span>{{ rowData.event }}</span>
        </div>
        <div>
          <span>Entitás:</span><span>{{ rowData.entity_name }}</span>
        </div>
        <div>
          <span>Kérés azonosító:</span><span>{{ rowData.request_id }}</span>
        </div>
        <div>
          <span>Belépés azonosító:</span><span>{{ rowData.uuid }}</span>
        </div>
        <div>
          <span>Szoftver azonosító:</span><span>{{ rowData.software_id }}</span>
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
        <div>
          <span>Code:</span><span>{{ rowData.code }}</span>
        </div>
        <div>
          <span>Error:</span><span>{{ rowData.error }}</span>
        </div>
      </div>
    </template>
    <template v-slot:displayContent="rowData">
      <div class="uriContent">
        <span>{{ rowData.method }}:</span> <span>{{ rowData.uri }}</span>
      </div>
      <div
        class="NaploContent"
        tabindex="0"
        @focus="onCtFocus"
        v-html="rowData.message"
      ></div>
    </template>
  </Grid>
</template>

<script>
export default {
  created: function () {
    getStore("mods").load((s, d) => (this.fastFilter = d));

    var st = getStore("audit");
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
.GridRow > td.displayContent > .GridCelContent {
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

.GridRow > td.displayContent > .GridCelContent .NaploContent:focus {
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
.AllData > div {
  display: table-row;
}
.AllData > div > span {
  display: table-cell;
  padding: 1px 5px;
}
.AllData > div > span:last-child {
  font-weight: bold;
}
.uriContent {
  margin: 2px 6px;
}
</style>
