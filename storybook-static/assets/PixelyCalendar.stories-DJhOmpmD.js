import{B as e,J as t,R as n,_ as r,_t as i,at as a,b as o,ft as s,g as c,h as l,ht as u,m as d,u as f,v as p,vt as m,x as h,y as g}from"./vue.esm-bundler-BoDJd7Xm.js";import{i as ee,t as _}from"./VBtn-CPec6oCN.js";import{n as te,o as ne,t as v}from"./VCard-CUETpW0b.js";import{t as y}from"./VCheckbox-BJsSlYrb.js";import{t as re}from"./VChip-D7hdcGAj.js";import{n as ie,t as ae}from"./VSpacer-Cm9me7Qx.js";import{t as b}from"./VIcon-C_qOaQYK.js";import{F as oe,L as se,R as ce,k as le,t as ue}from"./VSelect-DnADdSC9.js";import{t as x}from"./VTextField-BaOM8KgK.js";var de={class:`d-flex calendar-shell`},fe={class:`calendar-sidebar pa-4`},pe={class:`flex-grow-1 calendar-main`},me={class:`calendar-toolbar d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10`},he={class:`d-flex align-center ga-2`},ge={class:`text-subtitle-1 font-weight-bold ms-2`},_e={key:0,class:`calendar-month`},ve={class:`month-headrow d-grid`},ye={class:`month-grid d-grid`},be=[`onClick`],xe={class:`month-daynum text-caption font-weight-medium`},Se={class:`month-events`},S={key:0,class:`text-caption text-medium-emphasis`},C={key:1,class:`calendar-week`},w={class:`week-headrow d-grid`},T={class:`text-caption text-medium-emphasis`},E={class:`text-subtitle-1 font-weight-bold`},D={class:`week-body d-grid`},O={class:`week-hourcol`},k={class:`week-events`},A={class:`font-weight-medium text-truncate`},j={class:`text-caption`},Ce={key:2,class:`calendar-day pa-4`},we={key:0,class:`text-center py-12 text-medium-emphasis`},Te={key:3,class:`calendar-agenda pa-4`},Ee={key:0,class:`text-center py-12 text-medium-emphasis`},De={class:`text-subtitle-2 font-weight-bold mb-2`},Oe={class:`d-flex align-center justify-space-between pa-3`},ke={class:`d-flex align-center ga-3`},Ae={class:`text-body-2 font-weight-medium`},je={class:`text-caption text-medium-emphasis`},Me={class:`d-sm-flex ga-3`},M=864e5,N=h({__name:`PixelyCalendar`,props:{events:{default:()=>[]},categories:{default:()=>[{id:`business`,label:`Business`,color:`#5B5BF5`},{id:`personal`,label:`Personal`,color:`#4CAF50`},{id:`family`,label:`Family`,color:`#03A9F4`},{id:`holiday`,label:`Holiday`,color:`#F2A93B`},{id:`etc`,label:`ETC`,color:`#EF5350`}]},initialView:{default:`month`},initialDate:{default:new Date().toISOString().slice(0,10)}},setup(h){let N=h,P=a(N.initialView),F=a(new Date(N.initialDate)),I=a(N.categories.map(e=>e.id)),L=a(!1),R=a(``),z=[`Sun`,`Mon`,`Tue`,`Wed`,`Thu`,`Fri`,`Sat`],B=a({title:``,category:N.categories[0]?.id??`business`,startDate:N.initialDate,startTime:`09:00`,endTime:`10:00`,allDay:!1}),Ne=d(()=>Object.fromEntries(N.categories.map(e=>[e.id,e]))),V=d(()=>N.events.filter(e=>!e.category||I.value.includes(e.category)));function Pe(e){return new Date(e.getFullYear(),e.getMonth(),1)}function H(e){let t=new Date(e);return t.setHours(0,0,0,0),new Date(t.getTime()-t.getDay()*M)}function U(e,t){return new Date(e.getTime()+t*M)}function W(e,t){return e.getFullYear()===t.getFullYear()&&e.getMonth()===t.getMonth()&&e.getDate()===t.getDate()}function G(e){let t=typeof e==`string`?new Date(e):e;return`${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,`0`)}-${String(t.getDate()).padStart(2,`0`)}`}let K=new Date,Fe=d(()=>{let e=F.value;return P.value===`day`?e.toLocaleDateString(`en-US`,{weekday:`long`,month:`long`,day:`numeric`,year:`numeric`}):e.toLocaleDateString(`en-US`,{month:`long`,year:`numeric`})}),Ie=d(()=>{let e=H(Pe(F.value)),t=[],n=e;for(let e=0;e<42;e++)t.push({date:new Date(n),current:n.getMonth()===F.value.getMonth()}),n=U(n,1);return t}),q=d(()=>{let e=H(F.value);return Array.from({length:7},(t,n)=>U(e,n))}),J=d(()=>X(F.value)),Y=d(()=>{let e=new Map;for(let t of V.value){let n=G(t.start),r=e.get(n)??[];r.push(t),e.set(n,r)}return Array.from(e.entries()).sort(([e],[t])=>e.localeCompare(t)).map(([e,t])=>({date:new Date(`${e}T00:00:00`),items:t}))});function X(e){return V.value.filter(t=>W(new Date(t.start),e))}function Le(e){return X(e)}function Re(e){F.value=e,P.value===`month`&&(P.value=`day`)}function ze(){P.value===`month`?F.value=new Date(F.value.getFullYear(),F.value.getMonth()-1,1):P.value===`week`?F.value=U(F.value,-7):F.value=U(F.value,-1)}function Z(){P.value===`month`?F.value=new Date(F.value.getFullYear(),F.value.getMonth()+1,1):P.value===`week`?F.value=U(F.value,7):F.value=U(F.value,1)}function Be(){F.value=new Date}function Ve(e){I.value.includes(e)?I.value=I.value.filter(t=>t!==e):I.value=[...I.value,e]}function He(){R.value=`Add Event`,B.value={title:``,category:N.categories[0]?.id??`business`,startDate:G(F.value),startTime:`09:00`,endTime:`10:00`,allDay:!1},L.value=!0}function Q(e){let t=new Date(e);return Number.isNaN(t.getTime())?``:t.toLocaleTimeString(`en-US`,{hour:`numeric`,minute:`2-digit`})}function $(e){let t=e.category&&Ne.value[e.category]?.color||`#5B5BF5`;return{backgroundColor:`${t}1A`,color:t,borderLeft:`3px solid ${t}`}}return(a,d)=>(n(),c(v,{class:`pixely-calendar`,elevation:`0`,border:``,style:{borderRadius:`16px`,overflow:`hidden`}},{default:t(()=>[l(`div`,de,[l(`aside`,fe,[o(_,{color:`primary`,variant:`flat`,block:``,"prepend-icon":`mdi-plus`,class:`mb-6`,onClick:He},{default:t(()=>[...d[10]||=[g(` Add Event `,-1)]]),_:1}),d[11]||=l(`div`,{class:`text-caption text-medium-emphasis text-uppercase font-weight-bold mb-3`},` Event Categories `,-1),(n(!0),p(f,null,e(h.categories,e=>(n(),p(`div`,{key:e.id,class:`category-row d-flex align-center ga-2 py-1`},[o(y,{"model-value":I.value.includes(e.id),"hide-details":``,density:`compact`,color:`primary`,"onUpdate:modelValue":t=>Ve(e.id)},null,8,[`model-value`,`onUpdate:modelValue`]),o(re,{size:`small`,variant:`flat`,style:i({backgroundColor:`${e.color}1A`,color:e.color})},{default:t(()=>[g(m(e.label),1)]),_:2},1032,[`style`])]))),128))]),l(`div`,pe,[l(`div`,me,[l(`div`,he,[o(_,{icon:``,variant:`text`,size:`small`,onClick:ze},{default:t(()=>[o(b,{icon:`mdi-chevron-left`})]),_:1}),o(_,{icon:``,variant:`text`,size:`small`,onClick:Z},{default:t(()=>[o(b,{icon:`mdi-chevron-right`})]),_:1}),o(_,{variant:`tonal`,size:`small`,onClick:Be},{default:t(()=>[...d[12]||=[g(` Today `,-1)]]),_:1}),l(`span`,ge,m(Fe.value),1)]),o(ee,{modelValue:P.value,"onUpdate:modelValue":d[0]||=e=>P.value=e,mandatory:``,density:`compact`,divided:``},{default:t(()=>[o(_,{value:`month`,size:`small`},{default:t(()=>[...d[13]||=[g(` Month `,-1)]]),_:1}),o(_,{value:`week`,size:`small`},{default:t(()=>[...d[14]||=[g(` Week `,-1)]]),_:1}),o(_,{value:`day`,size:`small`},{default:t(()=>[...d[15]||=[g(` Day `,-1)]]),_:1}),o(_,{value:`agenda`,size:`small`},{default:t(()=>[...d[16]||=[g(` Agenda `,-1)]]),_:1})]),_:1},8,[`modelValue`])]),P.value===`month`?(n(),p(`div`,_e,[l(`div`,ve,[(n(),p(f,null,e(z,e=>l(`div`,{key:e,class:`month-headcell text-caption font-weight-medium text-medium-emphasis`},m(e),1)),64))]),l(`div`,ye,[(n(!0),p(f,null,e(Ie.value,(t,a)=>(n(),p(`button`,{key:a,type:`button`,class:u([`month-cell`,{"month-cell--muted":!t.current,"month-cell--today":W(t.date,s(K))}]),onClick:e=>Re(t.date)},[l(`span`,xe,m(t.date.getDate()),1),l(`div`,Se,[(n(!0),p(f,null,e(X(t.date).slice(0,3),e=>(n(),p(`span`,{key:e.id,class:`month-event text-caption text-truncate`,style:i($(e))},m(e.title),5))),128)),X(t.date).length>3?(n(),p(`span`,S,` +`+m(X(t.date).length-3)+` more `,1)):r(``,!0)])],10,be))),128))])])):P.value===`week`?(n(),p(`div`,C,[l(`div`,w,[d[17]||=l(`div`,{class:`week-hourcol`},null,-1),(n(!0),p(f,null,e(q.value,e=>(n(),p(`div`,{key:e.toISOString(),class:u([`week-headcell text-center py-2`,{"week-headcell--today":W(e,s(K))}])},[l(`div`,T,m(z[e.getDay()]),1),l(`div`,E,m(e.getDate()),1)],2))),128))]),l(`div`,D,[l(`div`,O,[(n(),p(f,null,e(24,e=>l(`div`,{key:e,class:`week-hour text-caption text-medium-emphasis`},m(String(e-1).padStart(2,`0`))+`:00 `,1)),64))]),(n(!0),p(f,null,e(q.value,t=>(n(),p(`div`,{key:`col-${t.toISOString()}`,class:u([`week-col`,{"week-col--today":W(t,s(K))}])},[(n(),p(f,null,e(24,e=>l(`div`,{key:e,class:`week-slot border-t border-opacity-10`})),64)),l(`div`,k,[(n(!0),p(f,null,e(Le(t),e=>(n(),p(`div`,{key:e.id,class:`week-event px-2 py-1 mb-1 text-caption`,style:i($(e))},[l(`div`,A,m(e.title),1),l(`div`,j,m(Q(e.start)),1)],4))),128))])],2))),128))])])):P.value===`day`?(n(),p(`div`,Ce,[J.value.length===0?(n(),p(`div`,we,` No events scheduled for this day. `)):(n(),c(le,{key:1,lines:`two`},{default:t(()=>[(n(!0),p(f,null,e(J.value,e=>(n(),c(oe,{key:e.id,class:`mb-2`},{prepend:t(()=>[l(`div`,{class:`day-event-dot`,style:i($(e))},null,4)]),default:t(()=>[o(se,{class:`font-weight-medium`},{default:t(()=>[g(m(e.title),1)]),_:2},1024),o(ce,{class:`text-caption`},{default:t(()=>[g(m(Q(e.start)),1),e.end?(n(),p(f,{key:0},[g(` — `+m(Q(e.end)),1)],64)):r(``,!0)]),_:2},1024)]),_:2},1024))),128))]),_:1}))])):(n(),p(`div`,Te,[Y.value.length===0?(n(),p(`div`,Ee,` No events to display. `)):r(``,!0),(n(!0),p(f,null,e(Y.value,r=>(n(),p(`div`,{key:r.date.toISOString(),class:`mb-6`},[l(`div`,De,m(r.date.toLocaleDateString(`en-US`,{weekday:`long`,month:`long`,day:`numeric`})),1),(n(!0),p(f,null,e(r.items,e=>(n(),c(v,{key:e.id,class:`mb-2`,elevation:`0`,border:``},{default:t(()=>[l(`div`,Oe,[l(`div`,ke,[l(`div`,{class:`day-event-dot`,style:i($(e))},null,4),l(`span`,Ae,m(e.title),1)]),l(`span`,je,m(Q(e.start)),1)])]),_:2},1024))),128))]))),128))]))])]),o(ie,{modelValue:L.value,"onUpdate:modelValue":d[9]||=e=>L.value=e,"max-width":`560`,persistent:``},{default:t(()=>[o(v,{title:`Add Event`},{default:t(()=>[o(te,null,{default:t(()=>[o(x,{modelValue:B.value.title,"onUpdate:modelValue":d[1]||=e=>B.value.title=e,label:`Event title`,required:``,class:`mb-2`},null,8,[`modelValue`]),o(ue,{modelValue:B.value.category,"onUpdate:modelValue":d[2]||=e=>B.value.category=e,items:h.categories.map(e=>({title:e.label,value:e.id})),label:`Category`,class:`mb-2`},null,8,[`modelValue`,`items`]),l(`div`,Me,[o(x,{modelValue:B.value.startDate,"onUpdate:modelValue":d[3]||=e=>B.value.startDate=e,type:`date`,label:`Date`},null,8,[`modelValue`]),o(x,{modelValue:B.value.startTime,"onUpdate:modelValue":d[4]||=e=>B.value.startTime=e,type:`time`,label:`Start`,disabled:B.value.allDay},null,8,[`modelValue`,`disabled`]),o(x,{modelValue:B.value.endTime,"onUpdate:modelValue":d[5]||=e=>B.value.endTime=e,type:`time`,label:`End`,disabled:B.value.allDay},null,8,[`modelValue`,`disabled`])]),o(y,{modelValue:B.value.allDay,"onUpdate:modelValue":d[6]||=e=>B.value.allDay=e,label:`All day`,"hide-details":``},null,8,[`modelValue`])]),_:1}),o(ne,null,{default:t(()=>[o(ae),o(_,{variant:`text`,onClick:d[7]||=e=>L.value=!1},{default:t(()=>[...d[18]||=[g(` Cancel `,-1)]]),_:1}),o(_,{color:`primary`,variant:`flat`,onClick:d[8]||=e=>L.value=!1},{default:t(()=>[...d[19]||=[g(` Add Event `,-1)]]),_:1})]),_:1})]),_:1})]),_:1},8,[`modelValue`])]),_:1}))}}),P=((e,t)=>{let n=e.__vccOpts||e;for(let[e,r]of t)n[e]=r;return n})(N,[[`__scopeId`,`data-v-2723f15d`]]);N.__docgenInfo={exportName:`default`,displayName:`PixelyCalendar`,description:``,tags:{},props:[{name:`events`,required:!1,type:{name:`Array`,elements:[{name:`PixelyCalendarEvent`}]},defaultValue:{func:!1,value:`() => []`}},{name:`categories`,required:!1,type:{name:`Array`,elements:[{name:`PixelyCalendarCategory`}]},defaultValue:{func:!1,value:`() => [
  { id: 'business', label: 'Business', color: '#5B5BF5' },
  { id: 'personal', label: 'Personal', color: '#4CAF50' },
  { id: 'family', label: 'Family', color: '#03A9F4' },
  { id: 'holiday', label: 'Holiday', color: '#F2A93B' },
  { id: 'etc', label: 'ETC', color: '#EF5350' },
]`}},{name:`initialView`,required:!1,type:{name:`union`,elements:[{name:`"month"`},{name:`"week"`},{name:`"day"`},{name:`"agenda"`}]},defaultValue:{func:!1,value:`'month'`}},{name:`initialDate`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`new Date().toISOString().slice(0, 10)`}}],sourceFiles:[`/var/www/resources/js/design-system/components/calendar/PixelyCalendar.vue`]};var F={title:`Design System / PixelyCalendar`,component:P,tags:[`autodocs`]},I={render:e=>({components:{PixelyCalendar:P},setup:()=>({args:e}),template:`<PixelyCalendar v-bind="args" />`}),parameters:{controls:{sortBy:`required`}},args:{initialView:`month`,initialDate:`2024-01-15`,events:[{id:`1`,title:`Team Meeting`,start:`2024-01-15T09:00:00`,end:`2024-01-15T10:00:00`,category:`business`},{id:`2`,title:`Lunch`,start:`2024-01-15T12:00:00`,allDay:!0,category:`personal`}],categories:[{id:`business`,label:`Business`,color:`#5B5BF5`},{id:`personal`,label:`Personal`,color:`#4CAF50`}]}},L={args:{initialView:`week`,initialDate:`2024-01-15`,events:[{id:`1`,title:`Team Meeting`,start:`2024-01-15T09:00:00`,end:`2024-01-15T10:00:00`,category:`business`}],categories:[{id:`business`,label:`Business`,color:`#5B5BF5`},{id:`personal`,label:`Personal`,color:`#4CAF50`}]}},R={args:{initialView:`day`,initialDate:`2024-01-15`,events:[{id:`1`,title:`Team Meeting`,start:`2024-01-15T09:00:00`,end:`2024-01-15T10:00:00`,category:`business`}],categories:[{id:`business`,label:`Business`,color:`#5B5BF5`},{id:`personal`,label:`Personal`,color:`#4CAF50`}]}},z={args:{initialView:`agenda`,initialDate:`2024-01-15`,events:[{id:`1`,title:`Team Meeting`,start:`2024-01-15T09:00:00`,end:`2024-01-15T10:00:00`,category:`business`},{id:`2`,title:`Client Call`,start:`2024-01-15T14:00:00`,end:`2024-01-15T15:00:00`,category:`personal`}],categories:[{id:`business`,label:`Business`,color:`#5B5BF5`},{id:`personal`,label:`Personal`,color:`#4CAF50`},{id:`family`,label:`Family`,color:`#03A9F4`}]}};I.parameters={...I.parameters,docs:{...I.parameters?.docs,source:{originalSource:`{
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
}`,...I.parameters?.docs?.source}}},L.parameters={...L.parameters,docs:{...L.parameters?.docs,source:{originalSource:`{
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
}`,...L.parameters?.docs?.source}}},R.parameters={...R.parameters,docs:{...R.parameters?.docs,source:{originalSource:`{
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
}`,...R.parameters?.docs?.source}}},z.parameters={...z.parameters,docs:{...z.parameters?.docs,source:{originalSource:`{
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
}`,...z.parameters?.docs?.source}}};var B=[`Default`,`WeekView`,`DayView`,`AgendaView`];export{z as AgendaView,R as DayView,I as Default,L as WeekView,B as __namedExportsOrder,F as default};