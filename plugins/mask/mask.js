/* empty file */
// global variable
 var myMask ;
 var myEnterKeyPressed = 0 ;
     
    function getForm(e) {
        // will get the parent Form of the e element
        if( e.tagName == 'FORM' )
            return e;
        else
        	if( e.parentElement == null )
				return e;
			else
				return getForm( e.parentElement ) ;
    }
 
    function myLocalHandler(  e,  t,  o){  	
        var myForm = getForm( t ) ; // to get the form associated with the input
        if( myForm.target != "" ) // if the form is directed to an external window
            myMask.disable() ;
            
        Ext.getBody().moveTo(0,0) ; // to fix a bug on ViewPort...
        myMask.show(); 
        myMask.enable(); // to be sure next masking is enable for next time
    };

    function myLocalKeyPressHandler(  e,  t,  o){
    	if( myEnterKeyPressed == e.ENTER && e.getKey() == e.ENTER ) 
    		e.stopEvent() ;
    	
    	myEnterKeyPressed = e.getKey()  ;
    };    
    
    Ext.onReady(function() {
        myMask = new Ext.LoadMask(Ext.getBody(), {removeMask:false});
        
        Ext.getBody().on('click', 
                myLocalHandler, 
                this, 
                { delegate: '.submit' ,
                 delay:    50 
                } );

        Ext.getBody().on('keypress', 
                myLocalKeyPressHandler, 
                this, 
                { delegate: '.submit' 
                } );
        
    }); 
    

	
	