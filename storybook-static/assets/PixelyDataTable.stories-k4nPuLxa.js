import{h,g as _,o as V,v as c,x as s,n as r,f as t,k as d,l as u,j as p,y as A,e as D}from"./vue.esm-bundler-1WDOaPC_.js";import{_ as N}from"./PixelyEmptyStateCentered-DX6cqKC4.js";import{V as P,a as T}from"./VCard-BGQyxci5.js";import{V as k}from"./VDataTable-Bf0wCMbi.js";import{V as f}from"./VBtn-B3lbe_XD.js";import{V as y}from"./VIcon-CsjJrb3h.js";import"./loader-uOTrFB-z.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./VChip-Ch_IghGA.js";import"./index-BepWvI7E.js";import"./group-BStXsvvJ.js";import"./VSelect-lCPYaJWV.js";import"./VTextField-DsrpnTV2.js";import"./VCheckboxBtn-CLodqsSs.js";const w={class:"d-flex align-center ga-3"},L={class:"text-subtitle-1 font-weight-medium"},F={key:0,class:"text-caption text-medium-emphasis"},q={key:0,class:"p-6 text-center"},l=h({__name:"PixelyDataTable",props:{title:{default:"Recent Activity"},subtitle:{default:"Latest CRM records"},items:{default:()=>[]},columns:{default:()=>["id","name","status","date"]},emptyMessage:{default:"No records found."}},setup(a){const e=a,m=_(e.items??[]),M=D(()=>!e.columns||e.columns.length===0?[]:typeof e.columns[0]=="string"?e.columns.map(n=>({title:n.charAt(0).toUpperCase()+n.slice(1),key:n})):e.columns);return V(()=>{e.items&&(m.value=e.items)}),(n,B)=>(r(),c(P,{class:"d-flex flex-column pa-0",style:{borderRadius:"16px",padding:"0",backgroundColor:"#FFFFFF"},elevation:"0",border:""},{default:s(()=>[t(T,{class:"d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10 border-gray-light"},{default:s(()=>[d("div",w,[d("span",L,u(a.title),1),a.subtitle?(r(),p("span",F,u(a.subtitle),1)):A("",!0)])]),_:1}),m.value.length===0?(r(),p("div",q,[t(N,{title:a.emptyMessage,icon:"mdi-inbox-outline"},null,8,["title"])])):(r(),c(k,{key:1,headers:M.value,items:m.value,"item-key":"id","show-select":"",class:"elevation-0","hide-default-footer":"",dense:""},{"item.actions":s(()=>[t(f,{icon:"",variant:"text",size:"small",color:"primary"},{default:s(()=>[t(y,{size:"18",icon:"mdi-pencil"})]),_:1}),t(f,{icon:"",variant:"text",size:"small",color:"error"},{default:s(()=>[t(y,{size:"18",icon:"mdi-delete"})]),_:1})]),_:2},1032,["headers","items"]))]),_:1}))}});l.__docgenInfo={exportName:"default",displayName:"PixelyDataTable",description:"",tags:{},props:[{name:"title",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Recent Activity'"}},{name:"subtitle",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Latest CRM records'"}},{name:"items",required:!1,type:{name:"Array",elements:[{name:"Record",elements:[{name:"string"},{name:"unknown"}]}]},defaultValue:{func:!1,value:"() => []"}},{name:"columns",required:!1,type:{name:"union",elements:[{name:"Array",elements:[{name:"{ title: string; key: string }"}]},{name:"Array",elements:[{name:"string"}]}]},defaultValue:{func:!1,value:"() => ['id', 'name', 'status', 'date']"}},{name:"emptyMessage",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'No records found.'"}}],sourceFiles:["/var/www/resources/js/design-system/components/crm/PixelyDataTable.vue"]};const Z={title:"CRM / PixelyDataTable",component:l,tags:["autodocs"]},i={render:()=>({components:{PixelyDataTable:l},template:`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="items"
        :columns="columns"
      />
    `,data(){return{items:[{id:"CRM-1024",name:"Acme Corp",status:"Active",date:"15 Nov 2026"},{id:"CRM-1025",name:"Globex Inc",status:"Pending",date:"14 Nov 2026"},{id:"CRM-1026",name:"Umbrella Ltd",status:"Active",date:"13 Nov 2026"}],columns:["id","name","status","date"]}}})},o={render:()=>({components:{PixelyDataTable:l},template:`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="[]"
        :columns="columns"
        emptyMessage="No CRM records found."
      />
    `,data(){return{columns:["id","name","status","date"]}}})};var b,v,g;i.parameters={...i.parameters,docs:{...(b=i.parameters)==null?void 0:b.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyDataTable
    },
    template: \`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="items"
        :columns="columns"
      />
    \`,
    data() {
      return {
        items: [{
          id: 'CRM-1024',
          name: 'Acme Corp',
          status: 'Active',
          date: '15 Nov 2026'
        }, {
          id: 'CRM-1025',
          name: 'Globex Inc',
          status: 'Pending',
          date: '14 Nov 2026'
        }, {
          id: 'CRM-1026',
          name: 'Umbrella Ltd',
          status: 'Active',
          date: '13 Nov 2026'
        }],
        columns: ['id', 'name', 'status', 'date']
      };
    }
  })
}`,...(g=(v=i.parameters)==null?void 0:v.docs)==null?void 0:g.source}}};var x,C,R;o.parameters={...o.parameters,docs:{...(x=o.parameters)==null?void 0:x.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyDataTable
    },
    template: \`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="[]"
        :columns="columns"
        emptyMessage="No CRM records found."
      />
    \`,
    data() {
      return {
        columns: ['id', 'name', 'status', 'date']
      };
    }
  })
}`,...(R=(C=o.parameters)==null?void 0:C.docs)==null?void 0:R.source}}};const ee=["Default","Empty"];export{i as Default,o as Empty,ee as __namedExportsOrder,Z as default};
