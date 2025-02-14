const one_way_trip=document.getElementById('one_way_trip');
const round_trip=document.getElementById('round_trip');
const return_date=document.getElementById('return_date');

one_way_trip.addEventListener('click',function(){
    return_date.style.display="none";
})
round_trip.addEventListener('click',function(){
    return_date.style.display="block";
})

