<template>
  <Panel border>
  
    <Form store="smtp" load :save="hasPerm('cnadmin')">
      <Field :noEdit="!hasPerm('cnadmin')"  type="text" label="Host" name="smtp_host_" required />
      <Field :noEdit="!hasPerm('cnadmin')"  type="text" label="Port" name="smtp_port_" required />
      <Field :noEdit="!hasPerm('cnadmin')"  type="text" label="Alapértelmezett feladó neve" name="smtp_sender_name_" info="Ha a modultól nem kapja, akkor ezzel küldi"/>
      <Field :noEdit="!hasPerm('cnadmin')"  type="text" label="Alapértelmezett feladó e-mail cím" name="smtp_sender_email_" info="Ha a modultól nem kapja, akkor ezzel küldi"/>
      <Field :noEdit="!hasPerm('cnadmin')"  type="rigennem" label="Titkosítás" name="smtp_security_" />

      <Field :noEdit="!hasPerm('cnadmin')" type="rigennem" label="Authentikáció" name="smtp_auth_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Authentikáció típusa" name="smtp_authtype_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Authentikációs felhaszálónév" name="smtp_username_" />
      <Field :noEdit="!hasPerm('cnadmin')" type="text" label="Authentikációs jelszó" name="smtp_password_" />
    </Form>
  </Panel>
</template>
