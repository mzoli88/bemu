<template>
  <Panel border>

    <Form store="push" load :save="hasPerm('cnadmin')">
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Firebase url" name="push_fb_url_" required />
      <Field :noEdit="!hasPerm('cnadmin')" type="textarea" label="Firebase json" name="push_fb_key_" required />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Android csatorna azonosító"
        name="push_fb_android_channel_id_" required />

      <Field :noEdit="!hasPerm('cnadmin')" type="rigennem" label="Proxy használat" name="proxy_use_" required />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Proxy szerver host" name="proxy_host_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Proxy szerver port" name="proxy_port_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Proxy felhasználónév" name="proxy_user_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Proxy felhasználó jelszó" name="proxy_pw_" />

    </Form>
  </Panel>
</template>
