
function callProgressBar(div, file_name) {
    fetch(file_name)
        .then((response) => response.json())
        .then((json) => {

            // set the alert level of the message box appropriately
            div.classList.remove(...div.classList); // take all classes out
            div.classList.add('alert'); // this is in all of them
            div.classList.add('alert-' + json.level);// add the level returned

            // write the message in that div
            div.querySelector('div').innerHTML = json.message;

            // get the image
            const img =  div.querySelector('img');
   
            // were we successful?
            if(json.image){

                // we have an image to display and we
                // move to the next once it has been displayed
                img.style.display = 'block';

                // we have a handler for 
                const handleImageLoad = (e) =>{

                    console.log(e);

                    // remove ourselves from the image so we don't do it every time
                    img.removeEventListener('load', handleImageLoad);

                    // if we haven't finished call for the next on
                    if (img.complete && !json.complete) callProgressBar(div, file_name);
                }

                // listen for when it is loaded
                img.addEventListener('load', handleImageLoad);

                // actually set the image
                img.src = json.image;
            
            }else{
                // we don't have an image to display so we just move to the next if appropriate
                if (!json.complete) callProgressBar(div, file_name);
                img.src = null;
                img.style.display = 'none';
            }
 
            if (json.complete){
                setTimeout(() => {
                    window.location.href = '/';
                }, 2000);
            }

        });

}



