<template>
  <Panel border>

    <Form store="general" load :save="hasPerm('cnadmin')">
      <Field :noEdit="!hasPerm('cnadmin')" type="number"
        label="Értesítések lista - szűrő feltétel nélküli keresés - lértehozás dátum intervalluma napokban"
        name="notification_list_date" required />

      <Field :noEdit="!hasPerm('cnadmin')" type="string"
        label="Email állományok elérési utvonala (storage könyvtár alatt)"
        name="notification_email_path" required />
    </Form>
  </Panel>
</template>
