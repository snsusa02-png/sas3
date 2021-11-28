-- SELECT * FROM 
update equiprqsts 
set orgid=initorgid 
where orgid is null;

update eritm_offers as ofr
set get_qty=(select sum(get_qty) from eritm_supplies as s where s.offerid=ofr.id)
