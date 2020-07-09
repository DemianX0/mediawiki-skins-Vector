declare class CheckboxHack {
	constructor( window: Window, checkbox: HTMLInputElement, button: HTMLElement, options?: CheckboxHackOptions );
	window   : Window;
	checkbox : HTMLInputElement;
	button   : HTMLElement;
	options  : CheckboxHackOptions;
	unbind() : void;
}

interface CheckboxHackOptions {
	noClickHandler?          : boolean;
	noKeyHandler?            : boolean;
	noDismissOnClickOutside? : boolean;
	noDismissOnFocusLoss?    : boolean;
	autoHideElement?         : Node;
	onChange?                : onChangeCallback;
}

//declare type onChangeCallback = ( this: CheckboxHack, event?: Event ) => void;

interface onChangeCallback {
	( this: CheckboxHack, event?: Event ) : void;
}

declare type ListenerType = EventListener | EventListenerObject;

interface CheckboxHackListeners {
	onStateChange?           : ListenerType;
	onButtonClick?           : ListenerType;
	onKeydownSpaceEnter?     : ListenerType;
	onKeyupSpaceEnter?       : ListenerType;
	onDismissOnClickOutside? : ListenerType;
	onDismissOnFocusLoss?    : ListenerType;
}
