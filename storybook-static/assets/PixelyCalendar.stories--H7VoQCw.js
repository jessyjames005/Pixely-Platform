import{h as ke,g as D,v as S,x as r,n as l,k as n,f as s,A as v,j as i,F as g,D as p,B as T,l as u,G as N,u as $,y as I,e as V}from"./vue.esm-bundler-1WDOaPC_.js";import{V as O,b as _e,d as Te}from"./VCard-BGQyxci5.js";import{V as f,a as Ce}from"./VBtn-B3lbe_XD.js";import{V as R}from"./VCheckbox-CVA9zmGm.js";import{V as Fe}from"./VChip-Ch_IghGA.js";import{V as J}from"./VIcon-CsjJrb3h.js";import{b as Be,c as Se,d as Ae,e as Me,f as Pe}from"./VSelect-lCPYaJWV.js";import{V as Ee,a as Ue}from"./VSpacer-c5pEa9st.js";import{a as A}from"./VTextField-DsrpnTV2.js";import"./loader-uOTrFB-z.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./group-BStXsvvJ.js";import"./VCheckboxBtn-CLodqsSs.js";import"./index-BepWvI7E.js";const Le={class:"d-flex calendar-shell"},Ne={class:"calendar-sidebar pa-4"},$e={class:"flex-grow-1 calendar-main"},Ie={class:"calendar-toolbar d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10"},Oe={class:"d-flex align-center ga-2"},ze={class:"text-subtitle-1 font-weight-bold ms-2"},Ye={key:0,class:"calendar-month"},qe={class:"month-headrow d-grid"},We={class:"month-grid d-grid"},je=["onClick"],He={class:"month-daynum text-caption font-weight-medium"},Ge={class:"month-events"},Ke={key:0,class:"text-caption text-medium-emphasis"},Re={key:1,class:"calendar-week"},Je={class:"week-headrow d-grid"},Qe={class:"text-caption text-medium-emphasis"},Xe={class:"text-subtitle-1 font-weight-bold"},Ze={class:"week-body d-grid"},et={class:"week-hourcol"},tt={class:"week-events"},at={class:"font-weight-medium text-truncate"},lt={class:"text-caption"},nt={key:2,class:"calendar-day pa-4"},st={key:0,class:"text-center py-12 text-medium-emphasis"},it={key:3,class:"calendar-agenda pa-4"},ot={key:0,class:"text-center py-12 text-medium-emphasis"},rt={class:"text-subtitle-2 font-weight-bold mb-2"},dt={class:"d-flex align-center justify-space-between pa-3"},ut={class:"d-flex align-center ga-3"},ct={class:"text-body-2 font-weight-medium"},mt={class:"text-caption text-medium-emphasis"},gt={class:"d-sm-flex ga-3"},Q=24*60*60*1e3,ue=ke({__name:"PixelyCalendar",props:{events:{default:()=>[]},categories:{default:()=>[{id:"business",label:"Business",color:"#5B5BF5"},{id:"personal",label:"Personal",color:"#4CAF50"},{id:"family",label:"Family",color:"#03A9F4"},{id:"holiday",label:"Holiday",color:"#F2A93B"},{id:"etc",label:"ETC",color:"#EF5350"}]},initialView:{default:"month"},initialDate:{default:new Date().toISOString().slice(0,10)}},setup(w){var K;const y=w,c=D(y.initialView),o=D(new Date(y.initialDate)),b=D(y.categories.map(a=>a.id)),k=D(!1),me=D(""),z=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],m=D({title:"",category:((K=y.categories[0])==null?void 0:K.id)??"business",startDate:y.initialDate,startTime:"09:00",endTime:"10:00",allDay:!1}),ge=V(()=>Object.fromEntries(y.categories.map(a=>[a.id,a]))),Y=V(()=>y.events.filter(a=>!a.category||b.value.includes(a.category)));function ve(a){return new Date(a.getFullYear(),a.getMonth(),1)}function q(a){const t=new Date(a);return t.setHours(0,0,0,0),new Date(t.getTime()-t.getDay()*Q)}function x(a,t){return new Date(a.getTime()+t*Q)}function C(a,t){return a.getFullYear()===t.getFullYear()&&a.getMonth()===t.getMonth()&&a.getDate()===t.getDate()}function W(a){const t=typeof a=="string"?new Date(a):a;return`${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,"0")}-${String(t.getDate()).padStart(2,"0")}`}const L=new Date,pe=V(()=>{const a=o.value;return c.value==="day"?a.toLocaleDateString("en-US",{weekday:"long",month:"long",day:"numeric",year:"numeric"}):a.toLocaleDateString("en-US",{month:"long",year:"numeric"})}),ye=V(()=>{const a=ve(o.value),t=q(a),e=[];let d=t;for(let h=0;h<42;h++)e.push({date:new Date(d),current:d.getMonth()===o.value.getMonth()}),d=x(d,1);return e}),j=V(()=>{const a=q(o.value);return Array.from({length:7},(t,e)=>x(a,e))}),H=V(()=>_(o.value)),G=V(()=>{const a=new Map;for(const t of Y.value){const e=W(t.start),d=a.get(e)??[];d.push(t),a.set(e,d)}return Array.from(a.entries()).sort(([t],[e])=>t.localeCompare(e)).map(([t,e])=>({date:new Date(`${t}T00:00:00`),items:e}))});function _(a){return Y.value.filter(t=>C(new Date(t.start),a))}function fe(a){return _(a)}function be(a){o.value=a,c.value==="month"&&(c.value="day")}function we(){c.value==="month"?o.value=new Date(o.value.getFullYear(),o.value.getMonth()-1,1):c.value==="week"?o.value=x(o.value,-7):o.value=x(o.value,-1)}function he(){c.value==="month"?o.value=new Date(o.value.getFullYear(),o.value.getMonth()+1,1):c.value==="week"?o.value=x(o.value,7):o.value=x(o.value,1)}function Ve(){o.value=new Date}function xe(a){b.value.includes(a)?b.value=b.value.filter(t=>t!==a):b.value=[...b.value,a]}function De(){var a;me.value="Add Event",m.value={title:"",category:((a=y.categories[0])==null?void 0:a.id)??"business",startDate:W(o.value),startTime:"09:00",endTime:"10:00",allDay:!1},k.value=!0}function F(a){const t=new Date(a);return Number.isNaN(t.getTime())?"":t.toLocaleTimeString("en-US",{hour:"numeric",minute:"2-digit"})}function B(a){var e;const t=a.category&&((e=ge.value[a.category])==null?void 0:e.color)||"#5B5BF5";return{backgroundColor:`${t}1A`,color:t,borderLeft:`3px solid ${t}`}}return(a,t)=>(l(),S(O,{class:"pixely-calendar",elevation:"0",border:"",style:{borderRadius:"16px",overflow:"hidden"}},{default:r(()=>[n("div",Le,[n("aside",Ne,[s(f,{color:"primary",variant:"flat",block:"","prepend-icon":"mdi-plus",class:"mb-6",onClick:De},{default:r(()=>[...t[10]||(t[10]=[v(" Add Event ",-1)])]),_:1}),t[11]||(t[11]=n("div",{class:"text-caption text-medium-emphasis text-uppercase font-weight-bold mb-3"}," Event Categories ",-1)),(l(!0),i(g,null,p(w.categories,e=>(l(),i("div",{key:e.id,class:"category-row d-flex align-center ga-2 py-1"},[s(R,{"model-value":b.value.includes(e.id),"hide-details":"",density:"compact",color:"primary","onUpdate:modelValue":d=>xe(e.id)},null,8,["model-value","onUpdate:modelValue"]),s(Fe,{size:"small",variant:"flat",style:T({backgroundColor:`${e.color}1A`,color:e.color})},{default:r(()=>[v(u(e.label),1)]),_:2},1032,["style"])]))),128))]),n("div",$e,[n("div",Ie,[n("div",Oe,[s(f,{icon:"",variant:"text",size:"small",onClick:we},{default:r(()=>[s(J,{icon:"mdi-chevron-left"})]),_:1}),s(f,{icon:"",variant:"text",size:"small",onClick:he},{default:r(()=>[s(J,{icon:"mdi-chevron-right"})]),_:1}),s(f,{variant:"tonal",size:"small",onClick:Ve},{default:r(()=>[...t[12]||(t[12]=[v(" Today ",-1)])]),_:1}),n("span",ze,u(pe.value),1)]),s(Ce,{modelValue:c.value,"onUpdate:modelValue":t[0]||(t[0]=e=>c.value=e),mandatory:"",density:"compact",divided:""},{default:r(()=>[s(f,{value:"month",size:"small"},{default:r(()=>[...t[13]||(t[13]=[v(" Month ",-1)])]),_:1}),s(f,{value:"week",size:"small"},{default:r(()=>[...t[14]||(t[14]=[v(" Week ",-1)])]),_:1}),s(f,{value:"day",size:"small"},{default:r(()=>[...t[15]||(t[15]=[v(" Day ",-1)])]),_:1}),s(f,{value:"agenda",size:"small"},{default:r(()=>[...t[16]||(t[16]=[v(" Agenda ",-1)])]),_:1})]),_:1},8,["modelValue"])]),c.value==="month"?(l(),i("div",Ye,[n("div",qe,[(l(),i(g,null,p(z,e=>n("div",{key:e,class:"month-headcell text-caption font-weight-medium text-medium-emphasis"},u(e),1)),64))]),n("div",We,[(l(!0),i(g,null,p(ye.value,(e,d)=>(l(),i("button",{key:d,type:"button",class:N(["month-cell",{"month-cell--muted":!e.current,"month-cell--today":C(e.date,$(L))}]),onClick:h=>be(e.date)},[n("span",He,u(e.date.getDate()),1),n("div",Ge,[(l(!0),i(g,null,p(_(e.date).slice(0,3),h=>(l(),i("span",{key:h.id,class:"month-event text-caption text-truncate",style:T(B(h))},u(h.title),5))),128)),_(e.date).length>3?(l(),i("span",Ke," +"+u(_(e.date).length-3)+" more ",1)):I("",!0)])],10,je))),128))])])):c.value==="week"?(l(),i("div",Re,[n("div",Je,[t[17]||(t[17]=n("div",{class:"week-hourcol"},null,-1)),(l(!0),i(g,null,p(j.value,e=>(l(),i("div",{key:e.toISOString(),class:N(["week-headcell text-center py-2",{"week-headcell--today":C(e,$(L))}])},[n("div",Qe,u(z[e.getDay()]),1),n("div",Xe,u(e.getDate()),1)],2))),128))]),n("div",Ze,[n("div",et,[(l(),i(g,null,p(24,e=>n("div",{key:e,class:"week-hour text-caption text-medium-emphasis"},u(String(e-1).padStart(2,"0"))+":00 ",1)),64))]),(l(!0),i(g,null,p(j.value,e=>(l(),i("div",{key:`col-${e.toISOString()}`,class:N(["week-col",{"week-col--today":C(e,$(L))}])},[(l(),i(g,null,p(24,d=>n("div",{key:d,class:"week-slot border-t border-opacity-10"})),64)),n("div",tt,[(l(!0),i(g,null,p(fe(e),d=>(l(),i("div",{key:d.id,class:"week-event px-2 py-1 mb-1 text-caption",style:T(B(d))},[n("div",at,u(d.title),1),n("div",lt,u(F(d.start)),1)],4))),128))])],2))),128))])])):c.value==="day"?(l(),i("div",nt,[H.value.length===0?(l(),i("div",st," No events scheduled for this day. ")):(l(),S(Be,{key:1,lines:"two"},{default:r(()=>[(l(!0),i(g,null,p(H.value,e=>(l(),S(Se,{key:e.id,class:"mb-2"},{prepend:r(()=>[n("div",{class:"day-event-dot",style:T(B(e))},null,4)]),default:r(()=>[s(Ae,{class:"font-weight-medium"},{default:r(()=>[v(u(e.title),1)]),_:2},1024),s(Me,{class:"text-caption"},{default:r(()=>[v(u(F(e.start)),1),e.end?(l(),i(g,{key:0},[v(" — "+u(F(e.end)),1)],64)):I("",!0)]),_:2},1024)]),_:2},1024))),128))]),_:1}))])):(l(),i("div",it,[G.value.length===0?(l(),i("div",ot," No events to display. ")):I("",!0),(l(!0),i(g,null,p(G.value,e=>(l(),i("div",{key:e.date.toISOString(),class:"mb-6"},[n("div",rt,u(e.date.toLocaleDateString("en-US",{weekday:"long",month:"long",day:"numeric"})),1),(l(!0),i(g,null,p(e.items,d=>(l(),S(O,{key:d.id,class:"mb-2",elevation:"0",border:""},{default:r(()=>[n("div",dt,[n("div",ut,[n("div",{class:"day-event-dot",style:T(B(d))},null,4),n("span",ct,u(d.title),1)]),n("span",mt,u(F(d.start)),1)])]),_:2},1024))),128))]))),128))]))])]),s(Ee,{modelValue:k.value,"onUpdate:modelValue":t[9]||(t[9]=e=>k.value=e),"max-width":"560",persistent:""},{default:r(()=>[s(O,{title:"Add Event"},{default:r(()=>[s(_e,null,{default:r(()=>[s(A,{modelValue:m.value.title,"onUpdate:modelValue":t[1]||(t[1]=e=>m.value.title=e),label:"Event title",required:"",class:"mb-2"},null,8,["modelValue"]),s(Pe,{modelValue:m.value.category,"onUpdate:modelValue":t[2]||(t[2]=e=>m.value.category=e),items:w.categories.map(e=>({title:e.label,value:e.id})),label:"Category",class:"mb-2"},null,8,["modelValue","items"]),n("div",gt,[s(A,{modelValue:m.value.startDate,"onUpdate:modelValue":t[3]||(t[3]=e=>m.value.startDate=e),type:"date",label:"Date"},null,8,["modelValue"]),s(A,{modelValue:m.value.startTime,"onUpdate:modelValue":t[4]||(t[4]=e=>m.value.startTime=e),type:"time",label:"Start",disabled:m.value.allDay},null,8,["modelValue","disabled"]),s(A,{modelValue:m.value.endTime,"onUpdate:modelValue":t[5]||(t[5]=e=>m.value.endTime=e),type:"time",label:"End",disabled:m.value.allDay},null,8,["modelValue","disabled"])]),s(R,{modelValue:m.value.allDay,"onUpdate:modelValue":t[6]||(t[6]=e=>m.value.allDay=e),label:"All day","hide-details":""},null,8,["modelValue"])]),_:1}),s(Te,null,{default:r(()=>[s(Ue),s(f,{variant:"text",onClick:t[7]||(t[7]=e=>k.value=!1)},{default:r(()=>[...t[18]||(t[18]=[v(" Cancel ",-1)])]),_:1}),s(f,{color:"primary",variant:"flat",onClick:t[8]||(t[8]=e=>k.value=!1)},{default:r(()=>[...t[19]||(t[19]=[v(" Add Event ",-1)])]),_:1})]),_:1})]),_:1})]),_:1},8,["modelValue"])]),_:1}))}}),vt=(w,y)=>{const c=w.__vccOpts||w;for(const[o,b]of y)c[o]=b;return c},ce=vt(ue,[["__scopeId","data-v-2723f15d"]]);ue.__docgenInfo={exportName:"default",displayName:"PixelyCalendar",description:"",tags:{},props:[{name:"events",required:!1,type:{name:"Array",elements:[{name:"PixelyCalendarEvent"}]},defaultValue:{func:!1,value:"() => []"}},{name:"categories",required:!1,type:{name:"Array",elements:[{name:"PixelyCalendarCategory"}]},defaultValue:{func:!1,value:`() => [
  { id: 'business', label: 'Business', color: '#5B5BF5' },
  { id: 'personal', label: 'Personal', color: '#4CAF50' },
  { id: 'family', label: 'Family', color: '#03A9F4' },
  { id: 'holiday', label: 'Holiday', color: '#F2A93B' },
  { id: 'etc', label: 'ETC', color: '#EF5350' },
]`}},{name:"initialView",required:!1,type:{name:"union",elements:[{name:'"month"'},{name:'"week"'},{name:'"day"'},{name:'"agenda"'}]},defaultValue:{func:!1,value:"'month'"}},{name:"initialDate",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"new Date().toISOString().slice(0, 10)"}}],sourceFiles:["/var/www/resources/js/design-system/components/calendar/PixelyCalendar.vue"]};const At={title:"Design System / PixelyCalendar",component:ce,tags:["autodocs"]},M={render:w=>({components:{PixelyCalendar:ce},setup:()=>({args:w}),template:'<PixelyCalendar v-bind="args" />'}),parameters:{controls:{sortBy:"required"}},args:{initialView:"month",initialDate:"2024-01-15",events:[{id:"1",title:"Team Meeting",start:"2024-01-15T09:00:00",end:"2024-01-15T10:00:00",category:"business"},{id:"2",title:"Lunch",start:"2024-01-15T12:00:00",allDay:!0,category:"personal"}],categories:[{id:"business",label:"Business",color:"#5B5BF5"},{id:"personal",label:"Personal",color:"#4CAF50"}]}},P={args:{initialView:"week",initialDate:"2024-01-15",events:[{id:"1",title:"Team Meeting",start:"2024-01-15T09:00:00",end:"2024-01-15T10:00:00",category:"business"}],categories:[{id:"business",label:"Business",color:"#5B5BF5"},{id:"personal",label:"Personal",color:"#4CAF50"}]}},E={args:{initialView:"day",initialDate:"2024-01-15",events:[{id:"1",title:"Team Meeting",start:"2024-01-15T09:00:00",end:"2024-01-15T10:00:00",category:"business"}],categories:[{id:"business",label:"Business",color:"#5B5BF5"},{id:"personal",label:"Personal",color:"#4CAF50"}]}},U={args:{initialView:"agenda",initialDate:"2024-01-15",events:[{id:"1",title:"Team Meeting",start:"2024-01-15T09:00:00",end:"2024-01-15T10:00:00",category:"business"},{id:"2",title:"Client Call",start:"2024-01-15T14:00:00",end:"2024-01-15T15:00:00",category:"personal"}],categories:[{id:"business",label:"Business",color:"#5B5BF5"},{id:"personal",label:"Personal",color:"#4CAF50"},{id:"family",label:"Family",color:"#03A9F4"}]}};var X,Z,ee;M.parameters={...M.parameters,docs:{...(X=M.parameters)==null?void 0:X.docs,source:{originalSource:`{
  render: args => ({
    components: {
      PixelyCalendar
    },
    setup: () => ({
      args
    }),
    template: '<PixelyCalendar v-bind="args" />'
  }),
  parameters: {
    controls: {
      sortBy: 'required'
    }
  },
  args: {
    initialView: 'month',
    initialDate: '2024-01-15',
    events: [{
      id: '1',
      title: 'Team Meeting',
      start: '2024-01-15T09:00:00',
      end: '2024-01-15T10:00:00',
      category: 'business'
    }, {
      id: '2',
      title: 'Lunch',
      start: '2024-01-15T12:00:00',
      allDay: true,
      category: 'personal'
    }],
    categories: [{
      id: 'business',
      label: 'Business',
      color: '#5B5BF5'
    }, {
      id: 'personal',
      label: 'Personal',
      color: '#4CAF50'
    }]
  }
}`,...(ee=(Z=M.parameters)==null?void 0:Z.docs)==null?void 0:ee.source}}};var te,ae,le;P.parameters={...P.parameters,docs:{...(te=P.parameters)==null?void 0:te.docs,source:{originalSource:`{
  args: {
    initialView: 'week',
    initialDate: '2024-01-15',
    events: [{
      id: '1',
      title: 'Team Meeting',
      start: '2024-01-15T09:00:00',
      end: '2024-01-15T10:00:00',
      category: 'business'
    }],
    categories: [{
      id: 'business',
      label: 'Business',
      color: '#5B5BF5'
    }, {
      id: 'personal',
      label: 'Personal',
      color: '#4CAF50'
    }]
  }
}`,...(le=(ae=P.parameters)==null?void 0:ae.docs)==null?void 0:le.source}}};var ne,se,ie;E.parameters={...E.parameters,docs:{...(ne=E.parameters)==null?void 0:ne.docs,source:{originalSource:`{
  args: {
    initialView: 'day',
    initialDate: '2024-01-15',
    events: [{
      id: '1',
      title: 'Team Meeting',
      start: '2024-01-15T09:00:00',
      end: '2024-01-15T10:00:00',
      category: 'business'
    }],
    categories: [{
      id: 'business',
      label: 'Business',
      color: '#5B5BF5'
    }, {
      id: 'personal',
      label: 'Personal',
      color: '#4CAF50'
    }]
  }
}`,...(ie=(se=E.parameters)==null?void 0:se.docs)==null?void 0:ie.source}}};var oe,re,de;U.parameters={...U.parameters,docs:{...(oe=U.parameters)==null?void 0:oe.docs,source:{originalSource:`{
  args: {
    initialView: 'agenda',
    initialDate: '2024-01-15',
    events: [{
      id: '1',
      title: 'Team Meeting',
      start: '2024-01-15T09:00:00',
      end: '2024-01-15T10:00:00',
      category: 'business'
    }, {
      id: '2',
      title: 'Client Call',
      start: '2024-01-15T14:00:00',
      end: '2024-01-15T15:00:00',
      category: 'personal'
    }],
    categories: [{
      id: 'business',
      label: 'Business',
      color: '#5B5BF5'
    }, {
      id: 'personal',
      label: 'Personal',
      color: '#4CAF50'
    }, {
      id: 'family',
      label: 'Family',
      color: '#03A9F4'
    }]
  }
}`,...(de=(re=U.parameters)==null?void 0:re.docs)==null?void 0:de.source}}};const Mt=["Default","WeekView","DayView","AgendaView"];export{U as AgendaView,E as DayView,M as Default,P as WeekView,Mt as __namedExportsOrder,At as default};
