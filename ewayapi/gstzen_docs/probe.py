import json, urllib.request, time, sys
BASE='https://my.gstzen.in/~gstzen/a/post-einvoice-data/einvoice-json/'
TOK='de3a3a01-273a-4a81-8b75-13fe37f14dc6'
def post(path, body):
    r=urllib.request.Request(BASE+path, data=json.dumps(body).encode(), headers={'Token':TOK,'Content-Type':'application/json'}, method='POST')
    try: return json.loads(urllib.request.urlopen(r, timeout=60).read())
    except urllib.error.HTTPError as e: return {'HTTP':e.code,'body':e.read().decode()[:500]}
docno='NCGT/'+time.strftime('%H%M%S')
inv={"Version":"1.1",
 "TranDtls":{"TaxSch":"GST","SupTyp":"B2B","RegRev":"N","IgstOnIntra":"N"},
 "DocDtls":{"Typ":"INV","No":docno,"Dt":time.strftime('%d/%m/%Y')},
 "SellerDtls":{"Gstin":"27AADCG4992P1ZT","LglNm":"Test Seller","Addr1":"Shop 1 Market Road","Loc":"Mumbai","Pin":400001,"Stcd":"27"},
 "BuyerDtls":{"Gstin":"29AADCG4992P1ZP","LglNm":"Test Buyer","Pos":"29","Addr1":"MG Road Block 2","Loc":"Bangalore","Pin":560001,"Stcd":"29"},
 "ItemList":[{"SlNo":"1","PrdDesc":"Maggi Aata Noodle","IsServc":"N","HsnCd":"19023010","Qty":10,"Unit":"PCS","UnitPrice":100,"TotAmt":1000,"Discount":0,"AssAmt":1000,"GstRt":18,"IgstAmt":180,"CgstAmt":0,"SgstAmt":0,"CesAmt":0,"TotItemVal":1180}],
 "ValDtls":{"AssVal":1000,"CgstVal":0,"SgstVal":0,"IgstVal":180,"CesVal":0,"Discount":0,"RndOffAmt":0,"TotInvVal":1180}}
step=sys.argv[1] if len(sys.argv)>1 else 'all'
r=post('',inv); print('GENERATE', json.dumps({k:r.get(k) for k in ['status','message','Irn','AckNo','AckDt','IrnStatus','EwbStatus','uuid','SignedQrCodeImgUrl','InvoicePdfUrl','Irp']} if 'status' in r else r))
json.dump(inv, open('last_inv.json','w'))
ewb=dict(inv); ewb['EwbDtls']={"TransId":"","TransName":"","Distance":990,"TransMode":"1","VehNo":"MH01AB1234","VehType":"R"}
r=post('genewb/',ewb); print('GENEWB', json.dumps({k:r.get(k) for k in ['status','message','EwbNo','EwbDt','EwbValidTill','EwbStatus','IrnStatus']} if 'status' in r else r))
c=dict(ewb); c.update({"CnlRsn":"2","CnlRem":"Data entry mistake"})
r=post('cancelewb/',c); print('CANCELEWB', json.dumps({k:r.get(k) for k in ['status','message','EwbStatus','IrnStatus']} if 'status' in r else r))
r=post('cancel/',c); print('CANCEL IRN', json.dumps({k:r.get(k) for k in ['status','message','IrnStatus','EwbStatus']} if 'status' in r else r))
