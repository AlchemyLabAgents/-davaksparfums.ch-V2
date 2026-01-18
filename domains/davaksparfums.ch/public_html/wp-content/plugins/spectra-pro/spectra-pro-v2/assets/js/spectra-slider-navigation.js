window.SpectraProSliderNav = {
    // Initialize the slider navigation.
    init( sliderId ) {
        // Get the slider wrapper.
        const sliderWrapper = document.querySelector( `[data-slider-id="${ sliderId }"]` );
        if ( ! sliderWrapper ) {
            return;
        }

        // Get the swiper instance.
        const swiper = sliderWrapper.querySelector( '.swiper' )?.swiper;
        if ( ! swiper ) {
            return;
        }

        // Get the next and previous buttons.
        const nextButtons = document.querySelectorAll( `.slider-${ sliderId }-next` );
        const prevButtons = document.querySelectorAll( `.slider-${ sliderId }-prev` );

        nextButtons.forEach( ( button ) => {
            //add class spectra-slider-triggers to the button
            button.classList.add( 'spectra-slider-triggers' );
            button.addEventListener( 'click', ( e ) => {
                e.preventDefault();
                swiper.slideNext();
            } );
        } );

        // Add the click event listener to the previous button.
        prevButtons.forEach( ( button ) => {
            //add class spectra-slider-triggers to the button
            button.classList.add( 'spectra-slider-triggers' );
            button.addEventListener( 'click', ( e ) => {
                e.preventDefault();
                swiper.slidePrev();
            } );
        } );
    }
};

// Initialize when document is ready.
document.addEventListener( 'DOMContentLoaded', () => {
    // Look for sliders with custom navigation enabled.
    const sliders = document.querySelectorAll( '[data-spectra-custom-navigation]' );

    // Initialize each slider.
    sliders.forEach( ( slider ) => {
        const sliderId = slider.getAttribute( 'data-slider-id' );
        if ( sliderId ) {
            window.SpectraProSliderNav.init( sliderId );
        }
    } );
} );
