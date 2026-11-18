import{h as F,v as u,n as l,x as s,k as n,f as d,B as m,l as a,j as E,y as f,A as k,e as p}from"./vue.esm-bundler-1WDOaPC_.js";import{V as w}from"./VAvatar-CZRbl-Tq.js";import{V as g}from"./VIcon-CsjJrb3h.js";import{V as h}from"./VChip-Ch_IghGA.js";import{V}from"./VCard-BGQyxci5.js";import"./loader-uOTrFB-z.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./index-BepWvI7E.js";import"./group-BStXsvvJ.js";const S={class:"d-flex align-center ga-3"},P={class:"d-flex flex-column"},q={class:"text-caption text-medium-emphasis text-uppercase font-weight-medium"},N={class:"text-h5 font-weight-bold mt-1"},A={key:0,class:"text-body-2 text-medium-emphasis mt-1"},i=F({__name:"PixelyStatCardBig",props:{label:{default:"Metric"},value:{default:"0"},subtitle:{},icon:{default:"mdi-chart-line"},iconColor:{default:"#5B5BF5"},iconBackground:{default:"#EDEDFE"},trend:{},trendDirection:{default:"up"}},setup(e){const t=e,c=p(()=>t.trendDirection==="down"?"#EF5350":t.trendDirection==="flat"?"#9CA3AF":"#4CAF50"),b=p(()=>t.trendDirection==="down"?"mdi-arrow-down":t.trendDirection==="flat"?"mdi-minus":"mdi-arrow-up");return(M,_)=>(l(),u(V,{class:"d-flex align-start justify-space-between py-5 px-6",style:{borderRadius:"16px",padding:"24px",backgroundColor:"#FFFFFF"},elevation:"0",border:""},{default:s(()=>[n("div",S,[d(w,{rounded:"lg",size:"44",style:m({backgroundColor:e.iconBackground})},{default:s(()=>[d(g,{icon:e.icon,color:e.iconColor},null,8,["icon","color"])]),_:1},8,["style"]),n("div",P,[n("span",q,a(e.label),1),n("div",N,a(e.value),1),e.subtitle?(l(),E("div",A,a(e.subtitle),1)):f("",!0)])]),e.trend?(l(),u(h,{key:0,size:"small",variant:"flat",style:m({backgroundColor:`${c.value}1A`,color:c.value,fontWeight:600})},{default:s(()=>[d(g,{icon:b.value,size:"14",start:""},null,8,["icon"]),k(" "+a(e.trend),1)]),_:1},8,["style"])):f("",!0)]),_:1}))}});i.__docgenInfo={exportName:"default",displayName:"PixelyStatCardBig",description:"",tags:{},props:[{name:"label",required:!0,type:{name:"string"},defaultValue:{func:!1,value:"'Metric'"}},{name:"value",required:!0,type:{name:"union",elements:[{name:"string"},{name:"number"}]},defaultValue:{func:!1,value:"'0'"}},{name:"subtitle",required:!1,type:{name:"string"}},{name:"icon",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'mdi-chart-line'"}},{name:"iconColor",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#5B5BF5'"}},{name:"iconBackground",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#EDEDFE'"}},{name:"trend",required:!1,type:{name:"string"}},{name:"trendDirection",required:!1,type:{name:"TrendDirection"},defaultValue:{func:!1,value:"'up'"}}],sourceFiles:["/var/www/resources/js/design-system/components/crm/PixelyStatCardBig.vue"]};const H={title:"CRM / PixelyStatCardBig",component:i,tags:["autodocs"]},r={render:()=>({components:{PixelyStatCardBig:i},template:`
      <PixelyStatCardBig
        label="Total Orders"
        value="155k"
        subtitle="Last 4 Month"
        trend="+22%"
        trendDirection="up"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    `})},o={render:()=>({components:{PixelyStatCardBig:i},template:`
      <PixelyStatCardBig
        label="New Customers"
        value="2,856"
        subtitle="Down from last week"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    `})};var y,B,C;r.parameters={...r.parameters,docs:{...(y=r.parameters)==null?void 0:y.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCardBig
    },
    template: \`
      <PixelyStatCardBig
        label="Total Orders"
        value="155k"
        subtitle="Last 4 Month"
        trend="+22%"
        trendDirection="up"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    \`
  })
}`,...(C=(B=r.parameters)==null?void 0:B.docs)==null?void 0:C.source}}};var x,v,D;o.parameters={...o.parameters,docs:{...(x=o.parameters)==null?void 0:x.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCardBig
    },
    template: \`
      <PixelyStatCardBig
        label="New Customers"
        value="2,856"
        subtitle="Down from last week"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    \`
  })
}`,...(D=(v=o.parameters)==null?void 0:v.docs)==null?void 0:D.source}}};const J=["Default","Declining"];export{o as Declining,r as Default,J as __namedExportsOrder,H as default};
