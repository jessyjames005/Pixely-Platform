import{R as e,g as t,x as n}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as r}from"./VTextField-BaOM8KgK.js";var i=n({__name:`PixelyPasswordField`,props:{label:{default:`Password`},modelValue:{default:``},errorMessages:{},autocomplete:{default:`current-password`}},emits:[`update:modelValue`],setup(n){return(i,a)=>(e(),t(r,{"model-value":n.modelValue??``,label:n.label,autocomplete:n.autocomplete,"error-messages":n.errorMessages,type:`password`,"append-inner-icon":`mdi-eye-outline`,"onUpdate:modelValue":a[0]||=e=>i.$emit(`update:modelValue`,e)},null,8,[`model-value`,`label`,`autocomplete`,`error-messages`]))}}),a=i;i.__docgenInfo={exportName:`default`,displayName:`PixelyPasswordField`,description:``,tags:{},props:[{name:`label`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Password'`}},{name:`modelValue`,required:!1,type:{name:`union`,elements:[{name:`string`},{name:`null`}]},defaultValue:{func:!1,value:`''`}},{name:`errorMessages`,required:!1,type:{name:`union`,elements:[{name:`string`},{name:`Array`,elements:[{name:`string`}]}]}},{name:`autocomplete`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'current-password'`}}],events:[{name:`update:modelValue`,type:{names:[`string`]}}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyPasswordField.vue`]};var o={title:`Design System / PixelyPasswordField`,component:a,tags:[`autodocs`]},s={render:()=>({components:{PixelyPasswordField:a},template:`
      <PixelyPasswordField label="Password" modelValue="Secret123" />
    `})},c={render:()=>({components:{PixelyPasswordField:a},template:`
      <PixelyPasswordField
        label="Password"
        modelValue=""
        :error-messages="['Password is required.']"
      />
    `})};s.parameters={...s.parameters,docs:{...s.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyPasswordField
    },
    template: \`
      <PixelyPasswordField label="Password" modelValue="Secret123" />
    \`
  })
}`,...s.parameters?.docs?.source}}},c.parameters={...c.parameters,docs:{...c.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyPasswordField
    },
    template: \`
      <PixelyPasswordField
        label="Password"
        modelValue=""
        :error-messages="['Password is required.']"
      />
    \`
  })
}`,...c.parameters?.docs?.source}}};var l=[`Default`,`WithError`];export{s as Default,c as WithError,l as __namedExportsOrder,o as default};