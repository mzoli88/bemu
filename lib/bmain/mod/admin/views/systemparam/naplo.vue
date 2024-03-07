<template>
  <Panel border>
    <Form store="admin.params" load :save="hasPerm('systemparam_edit','SysAdmin')">
      <Field
        type="rigennem"
        label="Debug"
        name="log_debug"
        startValue="N"
        required
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
    </Form>
  </Panel>
</template>

