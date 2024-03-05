<template>
  <Panel border>
    <Form store="admin.params" load :save="hasPerm('systemparam_edit','SysAdmin')">
      <Panel title="Jelszó követelmények" border padding>
        <Field
          type="number"
          label="Minimum hossz"
          name="password_size"
          min="8"
          startValue="8"
          max="32"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Numerikus karakterek száma"
          name="password_numeric_size"
          min="0"
          startValue="2"
          max="32"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Nagybetűs karakterek száma"
          name="password_capital_size"
          min="0"
          startValue="2"
          max="32"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Speciális karakterek száma"
          name="password_spec_char"
          min="0"
          startValue="0"
          max="10"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Legutóbbi jelszavak figyelése (db)"
          info='Ha a felhasználó megváltoztatja a jelszót, az új jelszónak különböznie kell a paraméterül beállított legutóbbi jelszavától. "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="password_user_last_paswords"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
      </Panel>
      <Panel title="Beléptetés védelem" border padding>
        <Field
          type="number"
          label="Belépési kísérletek száma"
          info='Ha valaki átlépi a sikertelen kísérletek számát, akkor a rendszer felfüggeszti a felhasználóját. "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="password_entry_requests"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Belépés késleltetése (mp)"
          info='Belépési kísérletek közötti hány másodpercet késleltessen a rendszer. "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="password_entry_delay"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
      </Panel>
      <Panel title="Felhasználó védelem" border padding>
        <Field
          type="number"
          label="Nem aktivált felhasználók türelmi ideje (nap)"
          name="users_activate_days"
          startValue="15"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Jelszó érvényessége (nap)"
          info='A rendszer új jelszót követel a felhasználóktól miután lejárt az érvényessége. "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="password_valid_days"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Felhasználó felfüggesztése (nap)"
          info='Ha a felhasználó nem használja a fiókját, mennyi idő után kerül felfüggesztett státuszba? "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="password_user_inactive_days"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
        <Field
          type="number"
          label="Inaktív felhasználó kiléptetése (perc)"
          info='Ha a belépés során a felhasználó nem aktív, akkora rendszer mennyi idő után léptesse ki? "0" - érték esetén ez a funkció kikapcsolt állapotban van.'
          name="user_inanctive_timeout"
          startValue="0"
          required
          :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
        />
      </Panel>
    </Form>
  </Panel>
</template>

