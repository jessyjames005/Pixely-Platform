import{h as c,v as P,n as w}from"./vue.esm-bundler-1WDOaPC_.js";import{a as f}from"./VTextField-DsrpnTV2.js";import"./index-BepWvI7E.js";import"./VIcon-CsjJrb3h.js";import"./index-B9d54lPJ.js";import"./loader-uOTrFB-z.js";const r=c({__name:"PixelyPasswordField",props:{label:{default:"Password"},modelValue:{default:""},errorMessages:{},autocomplete:{default:"current-password"}},emits:["update:modelValue"],setup(e){return(u,l)=>(w(),P(f,{"model-value":e.modelValue??"",label:e.label,autocomplete:e.autocomplete,"error-messages":e.errorMessages,type:"password","append-inner-icon":"mdi-eye-outline","onUpdate:modelValue":l[0]||(l[0]=p=>u.$emit("update:modelValue",p))},null,8,["model-value","label","autocomplete","error-messages"]))}});r.__docgenInfo={exportName:"default",displayName:"PixelyPasswordField",description:"",tags:{},props:[{name:"label",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Password'"}},{name:"modelValue",required:!1,type:{name:"union",elements:[{name:"string"},{name:"null"}]},defaultValue:{func:!1,value:"''"}},{name:"errorMessages",required:!1,type:{name:"union",elements:[{name:"string"},{name:"Array",elements:[{name:"string"}]}]}},{name:"autocomplete",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'current-password'"}}],events:[{name:"update:modelValue",type:{names:["string"]}}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyPasswordField.vue"]};const v={title:"Design System / PixelyPasswordField",component:r,tags:["autodocs"]},a={render:()=>({components:{PixelyPasswordField:r},template:`
      <PixelyPasswordField label="Password" modelValue="Secret123" />
    `})},s={render:()=>({components:{PixelyPasswordField:r},template:`
      <PixelyPasswordField
        label="Password"
        modelValue=""
        :error-messages="['Password is required.']"
      />
    `})};var o,n,t;a.parameters={...a.parameters,docs:{...(o=a.parameters)==null?void 0:o.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyPasswordField
    },
    template: \`
      <PixelyPasswordField label="Password" modelValue="Secret123" />
    \`
  })
}`,...(t=(n=a.parameters)==null?void 0:n.docs)==null?void 0:t.source}}};var d,m,i;s.parameters={...s.parameters,docs:{...(d=s.parameters)==null?void 0:d.docs,source:{originalSource:`{
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
}`,...(i=(m=s.parameters)==null?void 0:m.docs)==null?void 0:i.source}}};const q=["Default","WithError"];export{a as Default,s as WithError,q as __namedExportsOrder,v as default};
