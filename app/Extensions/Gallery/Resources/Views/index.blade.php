<h1>Gallery</h1>

<form method="GET" action="/gallery">
    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search..."
    >

    <button type="submit">
        Search
    </button>
</form>

@foreach($photos as $photo)
    <p>{{ $photo->title }}</p>
@endforeach

{{ $photos->links() }}
