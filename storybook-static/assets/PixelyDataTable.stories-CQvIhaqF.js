import{F as e,J as t,R as n,_ as r,at as i,b as a,g as o,h as s,m as c,v as l,vt as u,x as d}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as f}from"./PixelyEmptyStateCentered-DZLRtYUL.js";import{t as p}from"./VBtn-CPec6oCN.js";import{i as m,t as h}from"./VCard-CUETpW0b.js";import{t as g}from"./VDataTable-DIkAKRsg.js";import{t as _}from"./VIcon-C_qOaQYK.js";var v={class:`d-flex align-center ga-3`},y={class:`text-subtitle-1 font-weight-medium`},b={key:0,class:`text-caption text-medium-emphasis`},x={key:0,class:`p-6 text-center`},S=d({__name:`PixelyDataTable`,props:{title:{default:`Recent Activity`},subtitle:{default:`Latest CRM records`},items:{default:()=>[]},columns:{default:()=>[`id`,`name`,`status`,`date`]},emptyMessage:{default:`No records found.`}},setup(d){let S=d,C=i(S.items??[]),w=c(()=>!S.columns||S.columns.length===0?[]:typeof S.columns[0]==`string`?S.columns.map(e=>({title:e.charAt(0).toUpperCase()+e.slice(1),key:e})):S.columns);return e(()=>{S.items&&(C.value=S.items)}),(e,i)=>(n(),o(h,{class:`d-flex flex-column pa-0`,style:{borderRadius:`16px`,padding:`0`,backgroundColor:`#FFFFFF`},elevation:`0`,border:``},{default:t(()=>[a(m,{class:`d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10 border-gray-light`},{default:t(()=>[s(`div`,v,[s(`span`,y,u(d.title),1),d.subtitle?(n(),l(`span`,b,u(d.subtitle),1)):r(``,!0)])]),_:1}),C.value.length===0?(n(),l(`div`,x,[a(f,{title:d.emptyMessage,icon:`mdi-inbox-outline`},null,8,[`title`])])):(n(),o(g,{key:1,headers:w.value,items:C.value,"item-key":`id`,"show-select":``,class:`elevation-0`,"hide-default-footer":``,dense:``},{"item.actions":t(()=>[a(p,{icon:``,variant:`text`,size:`small`,color:`primary`},{default:t(()=>[a(_,{size:`18`,icon:`mdi-pencil`})]),_:1}),a(p,{icon:``,variant:`text`,size:`small`,color:`error`},{default:t(()=>[a(_,{size:`18`,icon:`mdi-delete`})]),_:1})]),_:2},1032,[`headers`,`items`]))]),_:1}))}}),C=S;S.__docgenInfo={exportName:`default`,displayName:`PixelyDataTable`,description:``,tags:{},props:[{name:`title`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Recent Activity'`}},{name:`subtitle`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Latest CRM records'`}},{name:`items`,required:!1,type:{name:`Array`,elements:[{name:`Record`,elements:[{name:`string`},{name:`unknown`}]}]},defaultValue:{func:!1,value:`() => []`}},{name:`columns`,required:!1,type:{name:`union`,elements:[{name:`Array`,elements:[{name:`{ title: string; key: string }`}]},{name:`Array`,elements:[{name:`string`}]}]},defaultValue:{func:!1,value:`() => ['id', 'name', 'status', 'date']`}},{name:`emptyMessage`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'No records found.'`}}],sourceFiles:[`/var/www/resources/js/design-system/components/crm/PixelyDataTable.vue`]};var w={title:`CRM / PixelyDataTable`,component:C,tags:[`autodocs`]},T={render:()=>({components:{PixelyDataTable:C},template:`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="items"
        :columns="columns"
      />
    `,data(){return{items:[{id:`CRM-1024`,name:`Acme Corp`,status:`Active`,date:`15 Nov 2026`},{id:`CRM-1025`,name:`Globex Inc`,status:`Pending`,date:`14 Nov 2026`},{id:`CRM-1026`,name:`Umbrella Ltd`,status:`Active`,date:`13 Nov 2026`}],columns:[`id`,`name`,`status`,`date`]}}})},E={render:()=>({components:{PixelyDataTable:C},template:`
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="[]"
        :columns="columns"
        emptyMessage="No CRM records found."
      />
    `,data(){return{columns:[`id`,`name`,`status`,`date`]}}})};T.parameters={...T.parameters,docs:{...T.parameters?.docs,source:{originalSource:`{
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
}`,...T.parameters?.docs?.source}}},E.parameters={...E.parameters,docs:{...E.parameters?.docs,source:{originalSource:`{
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
}`,...E.parameters?.docs?.source}}};var D=[`Default`,`Empty`];export{T as Default,E as Empty,D as __namedExportsOrder,w as default};