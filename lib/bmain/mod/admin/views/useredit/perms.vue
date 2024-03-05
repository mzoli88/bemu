<template>
  <Accordion v-if="data">
    <Panel v-for="(modul, key) in data" :key="key">
      <template #title>
        <icon>{{ modul.icon }}</icon>
        <span>{{ modul.name }}</span>
        <span v-if="modul.version"> ({{ modul.version }})</span>
      </template>
      <div class="hflex fit padding" style="align-items: baseline">
        <h2>Menü láthatóság:</h2>
        <div v-if="modul.menu" class="hflex gap fit">
          <span></span>
          <Button
            v-for="(menu, menu_azon) in modul.menu"
            :key="menu_azon + '#' + key + hasJog(key, menu_azon)"
            :icon="hasJog(key, menu_azon) ? 'f058' : ''"
            :highlight="hasJog(key, menu_azon)"
            @click="onToogle(key, menu_azon)"
            >{{ menu.name }}</Button
          >
        </div>
        <h2 v-if="ifhasTemplate(key)">Sablonkezelő:</h2>
        <div v-if="modul.menu && ifhasTemplate(key)" class="hflex gap fit">
          <div class="vflex">
            <span></span>
            <Button
              :key="'temp#' + key + hasJogTemplate(key)"
              :icon="hasJogTemplate(key) ? 'f058' : ''"
              :highlight="hasJogTemplate(key)"
              @click="onToogleTemplate(key)"
            >
              Sablon karbantartás
            </Button>
          </div>
        </div>
      </div>
      <div class="hflex fit padding" style="align-items: baseline">
        <h2>Elemi jogok:</h2>
        <div v-if="modul.menu" class="hflex gap fit">
          <div
            class="vflex"
            v-for="(perm, perm_azon) in modul.perms"
            :key="perm_azon + '#' + key + hasJog(key, perm_azon)"
          >
            <span></span>
            <Button
              :icon="hasJog(key, perm_azon) ? 'f058' : ''"
              :highlight="hasJog(key, perm_azon)"
              @click="onToogle(key, perm_azon)"
            >
              {{ perm }}
            </Button>
            <div
              class="cflex pointer permInfoIcon"
              :title="modul.permsInfo[perm_azon]"
              v-if="modul.permsInfo && modul.permsInfo[perm_azon]"
            >
              <Icon>f05a</Icon>
            </div>
          </div>
        </div>
      </div>
    </Panel>
  </Accordion>
</template>

<script>
export default {
  data: function () {
    return {
      data: null,
      perms: null,
      templates: null,
      hastemplates: null,
    };
  },
  created: function () {
    this.load();
  },
  methods: {
    onToogle: function (modul_azon, perm) {
      if (!getJog("user_perms") && !isSysAdmin()) return;

      getStore("perms").update(this.up("Detail").record_id, {
        modul_azon: modul_azon,
        perm: perm,
      });
      this.load();
    },
    onToogleTemplate: function (modul_azon) {
      if (!getJog("user_perms") && !isSysAdmin()) return;
      getStore("templateperms").update(this.up("Detail").record_id, {
        modul_azon: modul_azon,
      });
      this.load();
    },
    ifhasTemplate: function (modul_azon) {
      return this.hastemplates.includes(modul_azon);
    },
    hasJogTemplate: function (modul_azon) {
      return this.templates.includes(modul_azon);
    },
    hasJog: function (modul_azon, perm) {
      return this.perms[modul_azon + "#" + perm] ? true : false;
    },
    load: function () {
      getStore("perms").load(
        {
          user_id: this.up("Detail").record_id,
        },
        (s, d) => {
          this.data = d.data;
          this.perms = d.perms;
          this.templates = d.templates;
          this.hastemplates = d.hastemplates;
        }
      );
    },
  },
};
</script>

<style>
.permInfoIcon {
  color: green;
  margin-left: 10px;
}
</style>