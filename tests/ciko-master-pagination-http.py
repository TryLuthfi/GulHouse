from pathlib import Path
import urllib.request,urllib.parse,urllib.error,http.cookiejar,json,re
from lxml import html
root=Path(__file__).resolve().parents[1];out=root/'.e2e/master-pagination-20261021'
fixture=json.loads((out/'http-fixture-private.json').read_text());base='http://127.0.0.1:52875';checks=[]
def ck(n,ok):
 checks.append({'name':n,'status':'PASS' if ok else 'FAIL'})
 if not ok:raise RuntimeError('FAIL '+n)
def session():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(op,path,data=None):
 try:
  r=op.open(base+path if path.startswith('/') else path,data=urllib.parse.urlencode(data).encode() if data is not None else None,timeout=20)
 except urllib.error.HTTPError as e:r=e
 raw=r.read().decode('utf-8','replace');return r.status,raw,dict(r.headers),r.geturl()
def doc(raw):return html.fromstring(raw)
def form_values(node):
 data={}
 for e in node.xpath('.//input[@name]|.//select[@name]|.//textarea[@name]'):
  if 'disabled' in e.attrib:continue
  typ=e.get('type','text')
  if typ in ('submit','button','file'):continue
  if typ in ('checkbox','radio') and 'checked' not in e.attrib:continue
  if e.tag=='textarea':v=e.text or ''
  elif e.tag=='select':
   options=e.xpath('./option[@selected]') or e.xpath('./option')[:1];v=options[0].get('value',options[0].text or '') if options else ''
  else:v=e.get('value','')
  data[e.get('name')]=v
 return data
def login(role):
 op=session();code,raw,_,_=req(op,'/master/login');f=doc(raw).xpath('//form')[0];values=form_values(f);values.update(username='ciko_'+role,password=fixture['password']);code,raw,_,url=req(op,f.get('action'),values)
 ck(role+' isolated login succeeds',code==200 and '/dashboard' in url and 'A Database Error' not in raw)
 return op
try:
 anonymous=session();code,raw,headers,url=req(anonymous,'/master/master-form/rooms/1');ck('Unauthenticated lazy form redirects to login',code==200 and '/master/login' in url)
 admin=login('super_admin')
 for route,total in [('properties',123),('room-types',130),('rooms',123),('tenants',123)]:
  code,raw,headers,url=req(admin,'/master/'+route+'?per_page=10&page=2&sort='+('name' if route in ('room-types','tenants') else 'code'))
  page=doc(raw);ck(route+' page HTTP clean',code==200 and 'A PHP Error' not in raw and 'A Database Error' not in raw and 'Warning:' not in raw)
  ck(route+' exactly ten table rows',len(page.xpath('//table/tbody/tr'))==10)
  ck(route+' edit forms absent before click',not page.xpath('//div[contains(@id,"-edit-")]'))
  ck(route+' only current page edit triggers rendered',len(page.xpath('//*[@data-edit-form-url]'))==10)
  expected_metric=25 if route=='tenants' else total
  ck(route+' full summary independent from page',page.xpath('//*[contains(@class,"metric-card")]/strong')[0].text_content().strip()==str(expected_metric) and str(total) in page.xpath('//*[contains(@class,"master-page-summary")]')[0].text_content())
  ck(route+' pagination next link preserves page size',any('per_page=10' in x and 'page=3' in x for x in page.xpath('//a[@rel="next"]/@href')))
  ck(route+' bottom has one pager not duplicate controls',len(page.xpath('//select[@name="per_page"]'))==1 and len(page.xpath('//nav[@aria-label="Halaman tabel"]'))==1)
  code,fragment,headers,url=req(admin,'/master/master-form/'+route+'/1');node=doc(fragment);forms=node.xpath('//form');ck(route+' lazy GET returns only one edit form',code==200 and len(forms)==1 and '<html' not in fragment.lower() and 'Warning:' not in fragment)
  ck(route+' lazy form has private no-store', 'no-store' in headers.get('Cache-Control',''))
  f=forms[0];values=form_values(f);csrf=[k for k in values if 'csrf' in k.lower()];ck(route+' lazy form has CSRF token',len(csrf)==1 and bool(values[csrf[0]]))
  missing=dict(values);missing.pop(csrf[0]);code,raw,_,_=req(admin,f.get('action'),missing);ck(route+' save without CSRF rejected',code==403)
  field={'properties':'name','room-types':'description','rooms':'room_name','tenants':'fullname'}[route];old=values[field];new='CIKO HTTP '+route
  values[field]=new
  code,raw,_,url=req(admin,f.get('action'),values);ck(route+' lazy form actual POST accepted',code==200 and 'tersimpan.' in raw and 'A PHP Error' not in raw and 'A Database Error' not in raw)
  code,fragment,_,_=req(admin,'/master/master-form/'+route+'/1');saved=form_values(doc(fragment).xpath('//form')[0]);ck(route+' changed value persisted',saved[field]==new)
  saved[field]=old;code,raw,_,_=req(admin,doc(fragment).xpath('//form')[0].get('action'),saved);ck(route+' fixture value restored through same POST',code==200 and 'tersimpan.' in raw)
  code,raw,_,_=req(admin,'/master/master-form/'+route+'/999999');ck(route+' missing identity 404',code==404)
 viewer=login('viewer');code,raw,_,_=req(viewer,'/master/rooms');ck('Viewer can read paginated room list',code==200 and 'Menampilkan' in raw)
 for role in ['viewer','finance','maintenance']:
  op=viewer if role=='viewer' else login(role)
  for route in ['properties','room-types','rooms','tenants']:
   code,raw,_,_=req(op,'/master/master-form/'+route+'/1');ck(role+' cannot fetch edit form '+route,code==403)
 code,raw,_,_=req(admin,'/master/master-form/payments/1');ck('Invalid resource cannot escape form whitelist',code==404)
 result={'passed':len(checks),'failed':0,'checks':checks,'environment':'owned_synthetic_http','real_business_writes':0,'http_fixture':fixture['schema']}
except Exception as e:
 result={'passed':sum(c['status']=='PASS' for c in checks),'failed':1,'error':str(e),'checks':checks,'http_fixture':fixture['schema']}
(out/'ciko-http-results.json').write_text(json.dumps(result,indent=2));print(json.dumps({k:v for k,v in result.items() if k!='checks'}));raise SystemExit(bool(result['failed']))
