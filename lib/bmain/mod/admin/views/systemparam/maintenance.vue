<template>
  <Panel border>
    <Form store="admin.params" load :save="hasPerm('systemparam_edit','SysAdmin')">
      <Field
        label="Karbantartás üzemmód"
        type="rigennem"
        name="maintenance_mode"
        startValue="N"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        label="Karbantartás fejléce"
        type="text"
        name="maintance_header"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        label="Karbantartás szövege"
        type="textarea"
        name="maintance_text"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
    </Form>
  </Panel>
</template>