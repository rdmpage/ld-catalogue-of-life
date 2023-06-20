# ld-catalogue-of-life
Linked data version of the Catalogue of Life

## CoL downloads

Catalogue of Life Checklist 2023-05-15 [10.48580/dfs6](https://doi.org/10.48580/dfs6), in ChecklistBank as https://www.checklistbank.org/dataset/9893, download 0604a7e4-a73f-472a-a4ae-f0f68cf342f7

### CoL JSON-LD

CoL has JSON-LD for taxa, and for datasets, e.g. view source for  https://www.catalogueoflife.org/data/dataset/1167 to see an example. Note that it does not include DOIs or ORCIDs(!)


### Match to nomenclator ids

Can use [gnverifier](https://github.com/gnames/gnverifier) to match to LSIDs. 

```
gnverifier "Zorotypus lawrencei" -q -s "1,168" --format="pretty" --all_matches
```

Not terribly fast, but gives us a way to do this without writing much code. Note that for ION and IPNI we will get multiple hits.

### 
### Oxigraph

To start Oxigraph:

```
oxigraph_server -l . serve 
```

Uploads:

```
curl 'http://localhost:7878/store?default' --header Content-Type:application/n-triples --data-binary @zoro.nt --progress-bar
```

```
curl -f -X POST -H 'Content-Type:application/n-triples' -T zoro.nt http://localhost:7878/store
```

#### Duplicate triples CONSTRUCT

This is a known issue with Oxigraph, see [SPARQL CONSTRUCT queries do not remove duplicates](https://github.com/oxigraph/oxigraph/issues/525). 



