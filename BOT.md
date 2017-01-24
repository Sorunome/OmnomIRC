# Bot structure
## Relay structure
Inside of the `relays`-folder there is a folder per relay type, the folder name corrisponds to the relay ID.  
Inside of the folder for the relay type must be a file `relay.py` and a file `config.json`. Additional files may be placed there for including.  
The `relay.py` file must implement a class called `Relay`, the `config.json` file must implement some arguments.

### relay.py
The `relay.py` file must implement a class called `Relay`. For that you must first include the oirc functions: `import oirc_include as oirc`.  
The `Relay`-class must inherit from `oirc.OircRelay`.  
The following methods can be implemented:

| Name | Must | args | purpose |
|------|------|------|---------|
| initRelay  | no  | _none_ | some additional initializing, such as parsing config and thelike
| startRelay | yes | _none_ | starts the relay, based on the config etc.
| updateRelay | no | newCfg | update the relay, based on the passed new config
| stopRelay | yes | _none_ | trigger stopping of the relay
| joinThread | yes/no | _none_ | If stopRelay is non-blocking this must block until the relay is stopped
| relayMessage | yes | n1,n2,t,m,c,s,uid,curline | Relay messages to all attached clients
| relayTopic | no | s,c,i | relay a topic change to all clients

The following methods exist for easier usage:

| Name | args | purpose |
|------|------|---------|
| debug | s | Log a debug message
| info | s | Log an info message
| error | s | Log an error message
| getHandle | c | Get's a unique type of class, populated with `id` for the network ID and `channels` with all the channels this network is present in

### config.json
The `config.json` file implements some config stuff necesarry for the bot:

| Key | purpose |
|-----|---------|
| name | Name the relay type (e.g. IRC, Telegram etc.)
| version | Version string of the relay type, format major.minor.path
| defaultCfg | Default config of the relay
| editPattern | Edit pattern of how to edit the relay (more below)

#### editPattern
An edit pattern is an array of object things to display to be able to edit the config easily in the admin panel. The array contains of objects. Each object must have the key `type`, additional keys may have an effect:  
* `name`: display name of the object
* `var`: var to edit, object notation is working (e.g. `main.server`)

| type | var | name | additional | purpose
|------|-----|------|------------|--------
| text | yes | yes | _none_ | Text editing box
| number | yes | yes | _none_ | Number editing box
| checkbox | yes | yes | _none_ | boolean true/false checkbox. If `pattern` is present the editPattern will be visible if the checkbox is set
| dropdown | yes | yes | `options` (array) | Create a dropdown-box. The `options`-array consists of objects with `name` and `val`. Optionally a `pattern` can be set, which is another editPattern which will spawn aftre the dropdown if the option is selected
| newline | no | no | _none_ | displays an additional blank line
| info | no | yes | _none_ | Displays the name as information
| more | no | yes | `pattern` | A show/hide box where `pattern` is another editPattern to show/hide
