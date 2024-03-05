<template>
  <Panel border title="Jelszó módosítás">
    <Form store="user" save saveOkText="Sikeres módosítás!">
      <Field
        label="Email"
        type="text"
        name="email"
        v-show="false"
        autocomplete="username"
        :startValue="getUserData().email"
      />
      <Field
        label="Jelenlegi jelszó"
        type="password"
        name="current_password"
        required
        autocomplete="current-password"
      />
      <Field
        label="Új jelszó"
        type="password"
        name="password"
        required
        autocomplete="new-password"
      />
      <Field
        label="Új jelszó megerősítése"
        type="password"
        name="password_confirmation"
        autocomplete="password_confirmation"
        required
      />
    </Form>
  </Panel>
</template>