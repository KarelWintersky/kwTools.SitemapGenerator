0. Конечно нужен GPG-key

1. Для выпуска новой версии:

`dch -M -v N.N`

или 
`make dch`
и указываем версию в редакторе 
 
2. Для фиксации версии:

```
dch -M --release --distribution stable
```
или
```
make dchr
```

3. Build binary

```
make build
```

4. Update git

Commit, push.

5. Publish release
 
Publish release via github interface, attach .DEB file from /production directory

or push DEB file to repository: `dput -u XXX kwsitemapgenerator_VERSION_amd64.chang```
git co
```
es`

----------------

NB:

Примечание по ключам:
```
dch: 
  -m, --maintmaint
         Don't change (maintain) the maintainer details in the changelog entry
  -M, --controlmaint
         Use maintainer name and email from the debian/control Maintainer field
``` 

Примечание - была попытка сделать 
```
dch:
	export DEBFULLNAME="Karel Wintersky" && export DEBEMAIL="karel.wintersky@gmail.com" && dch -i

dchr:
	export DEBFULLNAME="Karel Wintersky" && export DEBEMAIL="karel.wintersky@gmail.com" && dch -r
```
