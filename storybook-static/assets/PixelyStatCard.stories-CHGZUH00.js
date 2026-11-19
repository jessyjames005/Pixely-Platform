import{h as E,v as u,x as l,n as s,k as t,l as a,B as m,f as d,A as B,y as p,j as k,e as f}from"./vue.esm-bundler-1WDOaPC_.js";import{V as S}from"./VCard-BGQyxci5.js";import{V as b}from"./VChip-Ch_IghGA.js";import{V as g}from"./VIcon-CsjJrb3h.js";import{V}from"./VAvatar-CZRbl-Tq.js";import"./loader-uOTrFB-z.js";import"./index-9ifeDQX3.js";import"./index-BepWvI7E.js";import"./group-BStXsvvJ.js";import"./index-B9d54lPJ.js";const w={class:"d-flex align-start justify-space-between mb-3"},P={class:"text-caption text-medium-emphasis"},q={class:"d-flex align-center ga-3"},N={class:"text-h6 font-weight-bold"},A={key:0,class:"text-caption text-medium-emphasis"},i=E({__name:"PixelyStatCard",props:{label:{},value:{},icon:{default:"mdi-chart-line"},iconColor:{default:"#5B5BF5"},iconBackground:{default:"#EDEDFE"},trend:{},trendDirection:{default:"up"},hint:{}},setup(e){const n=e,c=f(()=>n.trendDirection==="down"?"#EF5350":n.trendDirection==="flat"?"#9CA3AF":"#4CAF50"),F=f(()=>n.trendDirection==="down"?"mdi-arrow-down":n.trendDirection==="flat"?"mdi-minus":"mdi-arrow-up");return(_,z)=>(s(),u(S,{class:"pixely-stat-card",style:{borderRadius:"16px",padding:"20px",backgroundColor:"#FFFFFF"},elevation:"0",border:""},{default:l(()=>[t("div",w,[t("span",P,a(e.label),1),e.trend?(s(),u(b,{key:0,size:"x-small",variant:"flat",style:m({backgroundColor:`${c.value}1A`,color:c.value,fontWeight:600})},{default:l(()=>[d(g,{icon:F.value,size:"12",start:""},null,8,["icon"]),B(" "+a(e.trend),1)]),_:1},8,["style"])):p("",!0)]),t("div",q,[d(V,{rounded:"lg",size:"44",style:m({backgroundColor:e.iconBackground})},{default:l(()=>[d(g,{icon:e.icon,color:e.iconColor},null,8,["icon","color"])]),_:1},8,["style"]),t("div",null,[t("div",N,a(e.value),1),e.hint?(s(),k("div",A,a(e.hint),1)):p("",!0)])])]),_:1}))}});i.__docgenInfo={exportName:"default",displayName:"PixelyStatCard",description:"",tags:{},props:[{name:"label",required:!0,type:{name:"string"}},{name:"value",required:!0,type:{name:"union",elements:[{name:"string"},{name:"number"}]}},{name:"icon",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'mdi-chart-line'"}},{name:"iconColor",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#5B5BF5'"}},{name:"iconBackground",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#EDEDFE'"}},{name:"trend",required:!1,type:{name:"string"}},{name:"trendDirection",required:!1,type:{name:"TrendDirection"},defaultValue:{func:!1,value:"'up'"}},{name:"hint",required:!1,type:{name:"string"}}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyStatCard.vue"]};const H={title:"Design System / PixelyStatCard",component:i,tags:["autodocs"]},r={render:()=>({components:{PixelyStatCard:i},template:`
      <PixelyStatCard
        label="Total Orders"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="Last 4 Month"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    `})},o={render:()=>({components:{PixelyStatCard:i},template:`
      <PixelyStatCard
        label="New Customers"
        value="2,856"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    `})};var y,C,x;r.parameters={...r.parameters,docs:{...(y=r.parameters)==null?void 0:y.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCard
    },
    template: \`
      <PixelyStatCard
        label="Total Orders"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="Last 4 Month"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    \`
  })
}`,...(x=(C=r.parameters)==null?void 0:C.docs)==null?void 0:x.source}}};var v,D,h;o.parameters={...o.parameters,docs:{...(v=o.parameters)==null?void 0:v.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCard
    },
    template: \`
      <PixelyStatCard
        label="New Customers"
        value="2,856"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    \`
  })
}`,...(h=(D=o.parameters)==null?void 0:D.docs)==null?void 0:h.source}}};const J=["Default","Declining"];export{o as Declining,r as Default,J as __namedExportsOrder,H as default};
