

function get_session ( name ){
	
	
  var results = localStorage.getItem(name)
 
  if ( results )
    return results;
  else
    return "";
    	
}

function set_session(name, value){
	localStorage.setItem(name, value)
}